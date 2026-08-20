<?php

declare(strict_types=1);

namespace App\Domain\Ai\Agent\Schema;

/**
 * Builds the JSON schema definition for the agent's final structured reply.
 *
 * All properties are marked `required` and `additionalProperties` is false so
 * that OpenAI strict-mode enforcement is possible.
 */
final class JsonSchema
{
    /**
     * Return the full `response_format` payload for the OpenAI chat API.
     *
     * @param  string              $name   Schema name (visible in logs).
     * @param  array<string,mixed> $schema JSON Schema object describing `properties`.
     * @param  bool                $strict Whether to enable OpenAI strict mode.
     * @return array<string,mixed>
     */
    public static function responseFormat(string $name, array $schema, bool $strict = true): array
    {
        return [
            'type'        => 'json_schema',
            'json_schema' => [
                'name'   => $name,
                'strict' => $strict,
                'schema' => $schema,
            ],
        ];
    }

    /**
     * Schema for the agent's final reply — the only thing the customer will see.
     *
     * The `say` field uses citation placeholders: {{p:ID|field}} for properties,
     * {{k:ID}} for knowledge chunks.  ReplyRenderer resolves them from FactLedger.
     *
     * @return array<string,mixed>
     */
    public static function agentReplySchema(): array
    {
        return [
            'type'                 => 'object',
            'additionalProperties' => false,
            'required'             => ['say', 'cited_properties', 'cited_knowledge', 'brief_updates', 'confidence', 'needs_human'],
            'properties'           => [
                'say'               => [
                    'type'        => 'string',
                    'description' => 'Reply text in Saudi Arabic. Use {{p:ID|field}} for property facts and {{k:ID}} for KB citations. Never type a bare number for price, area, or date — always use a placeholder.',
                ],
                'cited_properties'  => [
                    'type'        => 'array',
                    'items'       => ['type' => 'integer'],
                    'description' => 'IDs of properties referenced via {{p:ID|...}} placeholders.',
                ],
                'cited_knowledge'   => [
                    'type'        => 'array',
                    'items'       => ['type' => 'string'],
                    'description' => 'chunk_id values cited via {{k:ID}} placeholders.',
                ],
                'brief_updates'     => [
                    'type'                 => 'object',
                    'additionalProperties' => false,
                    'description'          => 'New customer facts learned this turn. Set only the fields that changed; omit unchanged fields.',
                    'required'             => ['city', 'district', 'property_type', 'purpose', 'budget_min', 'budget_max', 'bedrooms', 'bathrooms', 'area_min', 'area_max', 'furnished', 'intent'],
                    'properties'           => [
                        'city'          => ['anyOf' => [['type' => 'string'], ['type' => 'null']], 'description' => 'Customer\'s preferred city.'],
                        'district'      => ['anyOf' => [['type' => 'string'], ['type' => 'null']], 'description' => 'Preferred district/neighbourhood.'],
                        'property_type' => ['anyOf' => [['type' => 'string'], ['type' => 'null']], 'description' => 'Property type e.g. شقة، فيلا، أرض.'],
                        'purpose'       => ['anyOf' => [['type' => 'string'], ['type' => 'null']], 'description' => 'sale or rent.'],
                        'budget_min'    => ['anyOf' => [['type' => 'number'], ['type' => 'null']], 'description' => 'Minimum budget in SAR.'],
                        'budget_max'    => ['anyOf' => [['type' => 'number'], ['type' => 'null']], 'description' => 'Maximum budget in SAR.'],
                        'bedrooms'      => ['anyOf' => [['type' => 'integer'], ['type' => 'null']], 'description' => 'Number of bedrooms.'],
                        'bathrooms'     => ['anyOf' => [['type' => 'integer'], ['type' => 'null']], 'description' => 'Number of bathrooms.'],
                        'area_min'      => ['anyOf' => [['type' => 'number'], ['type' => 'null']], 'description' => 'Minimum area in sqm.'],
                        'area_max'      => ['anyOf' => [['type' => 'number'], ['type' => 'null']], 'description' => 'Maximum area in sqm.'],
                        'furnished'     => ['anyOf' => [['type' => 'boolean'], ['type' => 'null']], 'description' => 'Whether furnished is required.'],
                        'intent'        => ['anyOf' => [['type' => 'string'], ['type' => 'null']], 'description' => 'search|viewing|knowledge|general.'],
                    ],
                ],
                'confidence'        => [
                    'type'        => 'integer',
                    'minimum'     => 0,
                    'maximum'     => 100,
                    'description' => 'Self-assessed confidence 0–100.',
                ],
                'needs_human'       => [
                    'type'        => 'boolean',
                    'description' => 'True if this turn requires a human agent.',
                ],
            ],
        ];
    }
}
