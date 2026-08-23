<?php

declare(strict_types=1);

namespace App\Domain\Ai\Agent\Runtime;

use App\Domain\Ai\Agent\Contracts\AgentTransport;
use App\Domain\Ai\Agent\DTOs\AgentMessage;
use App\Domain\Ai\Agent\DTOs\AgentStepRequest;
use App\Domain\Ai\Agent\DTOs\AgentStepResult;
use App\Domain\Ai\Agent\DTOs\ToolCall;
use App\Domain\Ai\Agent\Schema\JsonSchema;
use App\Domain\Ai\Agent\Schema\SchemaValidator;
use App\Domain\Ai\Exceptions\LlmProviderException;
use Illuminate\Support\Facades\Log;

/**
 * Provider-agnostic agent loop.
 *
 * Flow per turn:
 *   1. Send current message thread (system + history + current user message).
 *   2. If response contains tool_calls → dispatch each via ToolRegistry, append
 *      observations to thread, loop (up to StepBudget.maxSteps times).
 *   3. When budget reaches its second-to-last step → issue a forced-finalize
 *      request (tools=[], tool_choice=none) so budget_exhausted never reaches
 *      the customer (RC2 fix).
 *   4. Identical tool calls within the same turn return the cached result
 *      (tool-call dedupe) to prevent wasted steps.
 *   5. When no tool_calls → parse + schema-validate the final structured reply.
 *   6. If schema violations → one corrective retry with the violation list appended.
 *   7. Return AgentLoopResult with final reply, tool log, and budget snapshot.
 *
 * All network I/O goes through AgentTransport; the loop itself is stateless.
 */
final class AgentLoop
{
    public function __construct(
        private readonly AgentTransport $transport,
        private readonly ToolRegistry   $toolRegistry,
    ) {}

    /**
     * @param  AgentMessage[]          $initialMessages  system + history + current user turn
     * @param  int                     $tenantId
     * @param  string                  $model
     * @param  StepBudget              $budget
     * @param  int                     $maxTokensReply
     * @param  int                     $timeoutSeconds
     * @return AgentLoopResult
     */
    public function run(
        array       $initialMessages,
        int         $tenantId,
        string      $model,
        StepBudget  $budget,
        int         $maxTokensReply  = 800,
        int         $timeoutSeconds  = 30,
    ): AgentLoopResult {
        $messages      = $initialMessages;
        $steps         = [];
        $finalReply    = null;
        $failureReason = null;
        $toolCallLog   = [];
        $replySchema   = JsonSchema::agentReplySchema();
        $tools         = $this->toolRegistry->definitions();

        // Tool-call dedupe cache: "name:json_args" => result
        $toolResultCache = [];

        while (!$budget->isExhausted()) {
            // Forced finalization: on the last allowed step, strip tools so the
            // model must produce a final reply rather than another tool call.
            $isLastStep  = $budget->onLastStep();
            $stepTools   = $isLastStep ? [] : $tools;
            $toolChoice  = $isLastStep ? 'none' : null;

            $request = new AgentStepRequest(
                messages:       $messages,
                model:          $model,
                tools:          $stepTools,
                finalSchema:    $replySchema,
                maxTokens:      $maxTokensReply,
                temperature:    0.3,
                timeoutSeconds: $timeoutSeconds,
                toolChoice:     $toolChoice,
            );

            try {
                $result = $this->transport->step($request);
            } catch (LlmProviderException $e) {
                $failureReason = 'provider_error: ' . $e->getMessage();
                Log::error('agent.loop.provider_error', [
                    'tenant_id' => $tenantId,
                    'error'     => $e->getMessage(),
                    'step'      => $budget->stepsUsed(),
                ]);
                break;
            }

            $budget->recordStep($result->tokensIn, $result->tokensOut, $result->latencyMs);

            if ($result->wantsToolCall()) {
                $messages[] = AgentMessage::assistantToolCalls($result->toolCalls);

                foreach ($result->toolCalls as $toolCall) {
                    // Dedupe: return cached result for identical calls within the same turn
                    $cacheKey   = $toolCall->name . ':' . json_encode($toolCall->args, JSON_UNESCAPED_UNICODE);
                    if (isset($toolResultCache[$cacheKey])) {
                        $toolResult = array_merge(
                            $toolResultCache[$cacheKey],
                            ['already_ran' => true]
                        );
                        Log::debug('agent.loop.tool_dedupe', [
                            'tool' => $toolCall->name,
                            'step' => $budget->stepsUsed(),
                        ]);
                    } else {
                        $toolResult                  = $this->toolRegistry->dispatch($toolCall, $tenantId);
                        $toolResultCache[$cacheKey]  = $toolResult;
                    }

                    $toolCallLog[] = [
                        'id'     => $toolCall->id,
                        'name'   => $toolCall->name,
                        'args'   => $toolCall->args,
                        'result' => $toolResult,
                    ];

                    $messages[] = AgentMessage::toolResult(
                        $toolCall->id,
                        json_encode($toolResult, JSON_UNESCAPED_UNICODE) ?: '{}',
                    );
                }

                $steps[] = [
                    'type'       => 'tool_calls',
                    'tool_calls' => $toolCallLog,
                    'tokens_in'  => $result->tokensIn,
                    'tokens_out' => $result->tokensOut,
                    'latency_ms' => $result->latencyMs,
                ];
                continue;
            }

            if ($result->hasFinalReply()) {
                $decoded = $result->finalReply;
                $errors  = SchemaValidator::validate($decoded, $replySchema);

                if (count($errors) > 0) {
                    $errorText = implode('; ', $errors);
                    Log::warning('agent.loop.schema_violation', [
                        'tenant_id'  => $tenantId,
                        'violations' => $errors,
                        'step'       => $budget->stepsUsed(),
                    ]);

                    $messages[] = AgentMessage::assistant(json_encode($decoded, JSON_UNESCAPED_UNICODE) ?: '{}');
                    $messages[] = AgentMessage::user(
                        "Schema violations detected. Please correct your reply:\n" . $errorText
                    );

                    try {
                        $retry = $this->transport->step(new AgentStepRequest(
                            messages:       $messages,
                            model:          $model,
                            tools:          [],
                            finalSchema:    $replySchema,
                            maxTokens:      $maxTokensReply,
                            temperature:    0.1,
                            timeoutSeconds: $timeoutSeconds,
                        ));
                        $budget->recordStep($retry->tokensIn, $retry->tokensOut, $retry->latencyMs);

                        if ($retry->hasFinalReply()) {
                            $decoded2 = $retry->finalReply;
                            $errors2  = SchemaValidator::validate($decoded2, $replySchema);
                            if (count($errors2) === 0) {
                                $finalReply = $decoded2;
                            } else {
                                $failureReason = 'schema_violation_after_retry: ' . implode('; ', $errors2);
                            }
                        }
                    } catch (LlmProviderException $e) {
                        $failureReason = 'provider_error_on_retry: ' . $e->getMessage();
                    }
                } else {
                    $finalReply = $decoded;
                }

                $steps[] = [
                    'type'       => 'final_reply',
                    'raw'        => $decoded,
                    'violations' => $errors,
                    'tokens_in'  => $result->tokensIn,
                    'tokens_out' => $result->tokensOut,
                    'latency_ms' => $result->latencyMs,
                ];
                break;
            }

            // Neither tool calls nor final reply — model returned empty
            $failureReason = 'empty_response';
            break;
        }

        // With forced finalization this branch should be unreachable in practice,
        // but kept as a safety net.
        if ($finalReply === null && $failureReason === null && $budget->isExhausted()) {
            $failureReason = 'budget_exhausted';
        }

        return new AgentLoopResult(
            finalReply:    $finalReply,
            toolCallLog:   $toolCallLog,
            steps:         $steps,
            failureReason: $failureReason,
            budget:        $budget,
        );
    }
}
