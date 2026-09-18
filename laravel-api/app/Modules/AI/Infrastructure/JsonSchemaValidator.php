<?php

namespace App\Modules\AI\Infrastructure;

use App\Modules\AI\Domain\Exceptions\AiResponseException;

final class JsonSchemaValidator
{
    public static function validateOrFail(mixed $value, array $schema): void
    {
        $error = self::validate($value, $schema, '$');
        if ($error !== null) {
            throw new AiResponseException('AI returned an invalid structured response.');
        }
    }

    private static function validate(mixed $value, array $schema, string $path): ?string
    {
        if (isset($schema['enum']) && ! in_array($value, $schema['enum'], true)) {
            return $path.' is not an allowed value';
        }

        $type = $schema['type'] ?? null;
        if ($type === 'object') {
            if (! is_array($value) || array_is_list($value)) return $path.' must be an object';
            foreach ($schema['required'] ?? [] as $required) {
                if (! array_key_exists($required, $value)) return $path.'.'.$required.' is required';
            }
            $properties = $schema['properties'] ?? [];
            if (($schema['additionalProperties'] ?? true) === false) {
                foreach (array_keys($value) as $key) {
                    if (! array_key_exists($key, $properties)) return $path.'.'.$key.' is not allowed';
                }
            }
            foreach ($properties as $key => $propertySchema) {
                if (array_key_exists($key, $value)) {
                    $error = self::validate($value[$key], (array) $propertySchema, $path.'.'.$key);
                    if ($error !== null) return $error;
                }
            }
            return null;
        }

        if ($type === 'array') {
            if (! is_array($value)) return $path.' must be an array';
            if (isset($schema['minItems']) && count($value) < $schema['minItems']) return $path.' has too few items';
            if (isset($schema['maxItems']) && count($value) > $schema['maxItems']) return $path.' has too many items';
            foreach ($value as $index => $item) {
                if (isset($schema['items'])) {
                    $error = self::validate($item, (array) $schema['items'], $path.'.'.$index);
                    if ($error !== null) return $error;
                }
            }
            return null;
        }

        $valid = match ($type) {
            'string' => is_string($value),
            'number' => is_int($value) || is_float($value),
            'integer' => is_int($value),
            'boolean' => is_bool($value),
            'null' => $value === null,
            default => true,
        };
        if (! $valid) return $path.' has an invalid type';
        if (is_string($value)) {
            if (isset($schema['minLength']) && mb_strlen($value) < $schema['minLength']) return $path.' is too short';
            if (isset($schema['maxLength']) && mb_strlen($value) > $schema['maxLength']) return $path.' is too long';
        }
        if (is_int($value) || is_float($value)) {
            if (isset($schema['minimum']) && $value < $schema['minimum']) return $path.' is below minimum';
            if (isset($schema['maximum']) && $value > $schema['maximum']) return $path.' is above maximum';
        }
        return null;
    }
}
