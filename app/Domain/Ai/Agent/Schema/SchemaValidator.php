<?php

declare(strict_types=1);

namespace App\Domain\Ai\Agent\Schema;

/**
 * Validates decoded JSON against a subset of JSON Schema.
 *
 * This is a lightweight local validator — we do not trust the provider to enforce
 * the schema even in "strict" mode (models occasionally violate it under load).
 *
 * Covers: required, type, minimum/maximum for integers, enum, additionalProperties.
 */
final class SchemaValidator
{
    /**
     * @param  array<string, mixed> $data    Decoded JSON payload.
     * @param  array<string, mixed> $schema  JSON Schema object.
     * @return string[] List of violation messages; empty means valid.
     */
    public static function validate(array $data, array $schema): array
    {
        $errors = [];
        self::validateObject($data, $schema, '$', $errors);
        return $errors;
    }

    private static function validateObject(mixed $data, array $schema, string $path, array &$errors): void
    {
        // Handle anyOf (used for nullable typed fields: anyOf [{type: X}, {type: null}])
        if (isset($schema['anyOf'])) {
            $subErrors = [];
            foreach ($schema['anyOf'] as $subSchema) {
                $sub = [];
                self::validateObject($data, $subSchema, $path, $sub);
                if (empty($sub)) {
                    return; // At least one branch passes
                }
                $subErrors[] = $sub;
            }
            $errors[] = "{$path}: value did not match any anyOf schema";
            return;
        }

        $type = $schema['type'] ?? null;

        // Explicit null type
        if ($type === 'null') {
            if ($data !== null) {
                $errors[] = "{$path}: expected null, got " . gettype($data);
            }
            return;
        }

        if ($type === 'object') {
            if (!is_array($data)) {
                $errors[] = "{$path}: expected object, got " . gettype($data);
                return;
            }

            $required = $schema['required'] ?? [];
            foreach ($required as $key) {
                if (!array_key_exists($key, $data)) {
                    $errors[] = "{$path}.{$key}: required field missing";
                }
            }

            $additionalAllowed = $schema['additionalProperties'] ?? true;
            $knownProps        = array_keys($schema['properties'] ?? []);

            if ($additionalAllowed === false) {
                foreach (array_keys($data) as $key) {
                    if (!in_array($key, $knownProps, true)) {
                        $errors[] = "{$path}.{$key}: additional property not allowed";
                    }
                }
            }

            foreach ($schema['properties'] ?? [] as $key => $propSchema) {
                if (!array_key_exists($key, $data)) {
                    continue; // missing required already caught above
                }
                self::validateObject($data[$key], $propSchema, "{$path}.{$key}", $errors);
            }
            return;
        }

        if ($type === 'array') {
            if (!is_array($data)) {
                $errors[] = "{$path}: expected array, got " . gettype($data);
                return;
            }
            $itemSchema = $schema['items'] ?? null;
            if ($itemSchema !== null) {
                foreach ($data as $i => $item) {
                    self::validateObject($item, $itemSchema, "{$path}[{$i}]", $errors);
                }
            }
            return;
        }

        if ($type === 'string') {
            if (!is_string($data)) {
                $errors[] = "{$path}: expected string, got " . gettype($data);
            }
            if (isset($schema['enum']) && !in_array($data, $schema['enum'], true)) {
                $errors[] = "{$path}: value not in enum " . json_encode($schema['enum']);
            }
            return;
        }

        if ($type === 'integer') {
            if (!is_int($data)) {
                $errors[] = "{$path}: expected integer, got " . gettype($data);
                return;
            }
            if (isset($schema['minimum']) && $data < $schema['minimum']) {
                $errors[] = "{$path}: {$data} < minimum {$schema['minimum']}";
            }
            if (isset($schema['maximum']) && $data > $schema['maximum']) {
                $errors[] = "{$path}: {$data} > maximum {$schema['maximum']}";
            }
            return;
        }

        if ($type === 'number') {
            if (!is_int($data) && !is_float($data)) {
                $errors[] = "{$path}: expected number, got " . gettype($data);
            }
            return;
        }

        if ($type === 'boolean') {
            if (!is_bool($data)) {
                $errors[] = "{$path}: expected boolean, got " . gettype($data);
            }
            return;
        }
    }
}
