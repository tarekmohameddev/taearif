<?php

namespace App\Http\Controllers\Api\V1\Rms;

use App\Http\Controllers\Api\BaseApiController;
use App\Models\Api\Rms\RmsPaymentReminderAutomation;
use App\Services\Rms\PaymentReminderAutomationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class PaymentReminderAutomationController extends BaseApiController
{
    public function __construct(private readonly PaymentReminderAutomationService $service) {}

    public function show()
    {
        return $this->success($this->service->configuration($this->getUserId()));
    }

    public function update(Request $request)
    {
        $validated = Validator::make($request->all(), [
            'enabled' => ['required', 'boolean'], 'channel_type' => ['required', Rule::in(['whatsapp', 'sms'])],
            'channel_id' => ['nullable', 'integer', 'required_if:channel_type,whatsapp'],
            'channel_name' => ['nullable', 'string', 'max:255'], 'sender_id' => ['nullable', 'string', 'max:128'],
            'sending_time' => ['required', 'date_format:H:i'], 'timezone' => ['required', 'timezone'],
            'rules' => ['required', 'array', 'min:1', 'max:20'],
            'rules.*.key' => ['required', 'string', 'max:80', 'distinct'],
            'rules.*.stage' => ['required', Rule::in(['before_due', 'due_today', 'overdue', 'overdue_recurring'])],
            'rules.*.days_offset' => ['required', 'integer', 'between:-365,365'],
            'rules.*.interval_days' => ['nullable', 'integer', 'between:1,365'],
            'rules.*.recurring' => ['nullable', 'boolean'], 'rules.*.enabled' => ['required', 'boolean'],
            'rules.*.priority' => ['nullable', 'integer', 'between:1,1000'],
            'rules.*.template' => ['required', 'string', 'max:2000'],
            'rules.*.wa_template_id' => ['nullable', 'integer', 'min:1'],
            'rules.*.meta_variable_map' => ['nullable', 'array', 'max:20'],
            'rules.*.meta_variable_map.*' => ['string', Rule::in(['tenant_name', 'due_date', 'rental_reference', 'unit_name', 'balance'])],
        ])->validate();
        $ruleErrors = [];
        foreach ($validated['rules'] as $index => $rule) {
            $path = "rules.{$index}";
            $offset = (int) $rule['days_offset'];
            $isRecurring = ($rule['recurring'] ?? false) === true || ($rule['recurring'] ?? null) === 1 || ($rule['recurring'] ?? null) === '1';
            if ($rule['stage'] === 'before_due' && $offset >= 0) {
                $ruleErrors["{$path}.days_offset"][] = 'before_due requires a negative days_offset.';
            } elseif ($rule['stage'] === 'due_today' && $offset !== 0) {
                $ruleErrors["{$path}.days_offset"][] = 'due_today requires days_offset to be zero.';
            } elseif ($rule['stage'] === 'overdue' && ($offset <= 0 || $isRecurring)) {
                $ruleErrors["{$path}.days_offset"][] = 'overdue requires a positive days_offset and recurring=false.';
            } elseif ($rule['stage'] === 'overdue_recurring') {
                if ($offset <= 0) $ruleErrors["{$path}.days_offset"][] = 'overdue_recurring requires a positive days_offset.';
                if (! $isRecurring) $ruleErrors["{$path}.recurring"][] = 'overdue_recurring requires recurring=true.';
                if (empty($rule['interval_days']) || (int) $rule['interval_days'] < 1) $ruleErrors["{$path}.interval_days"][] = 'overdue_recurring requires a positive interval_days.';
            } elseif ($isRecurring) {
                $ruleErrors["{$path}.stage"][] = 'Recurring rules must use the overdue_recurring stage.';
            }
        }
        if ($ruleErrors) {
            return response()->json([
                'status' => false,
                'message' => 'The reminder rules have invalid stage and timing combinations.',
                'errors' => $ruleErrors,
            ], 422);
        }
        $data = $this->service->save($this->getUserId(), $validated);
        return $this->success($data, 'Payment reminder automation saved.');
    }

    public function logs(Request $request)
    {
        $validated = $request->validate([
            'from_date' => ['nullable', 'date_format:Y-m-d'], 'to_date' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:from_date'],
            'channel_type' => ['nullable', Rule::in(['whatsapp', 'sms'])], 'phone_number' => ['nullable', 'string', 'max:32'],
            'messages_sent' => ['nullable', 'integer', 'min:0'], 'messages_sent_min' => ['nullable', 'integer', 'min:0'],
            'messages_sent_max' => ['nullable', 'integer', 'min:0'], 'rental_id' => ['nullable', 'integer'],
            'installment_id' => ['nullable', 'integer'], 'stage' => ['nullable', 'string', 'max:32'],
            'status' => ['nullable', Rule::in(['pending', 'sending', 'sent', 'delivered', 'failed', 'skipped'])],
            'page' => ['nullable', 'integer', 'min:1'], 'per_page' => ['nullable', 'integer', 'between:1,100'],
        ]);
        if (isset($validated['messages_sent_min'], $validated['messages_sent_max']) && $validated['messages_sent_min'] > $validated['messages_sent_max']) {
            return response()->json(['status' => false, 'message' => 'messages_sent_min must be less than or equal to messages_sent_max.', 'errors' => ['messages_sent_max' => ['Invalid count range.']]], 422);
        }
        return $this->success($this->service->logs($this->getUserId(), $validated));
    }
}
