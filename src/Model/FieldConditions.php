<?php

declare(strict_types=1);

namespace Kalakotra\EFormKinderzuschlag\Model;

/**
 * Evaluates the declarative field dependency rules defined in Submission::$field_conditions.
 *
 * Rule format: [[dependent fields...], [controller => expected, ...], [alternative...], ...]
 *   - one condition set: all controllers must match (AND)
 *   - several condition sets: at least one set must match (OR)
 *   - expected string  -> controller must equal it ('!x' -> must NOT equal x)
 *   - expected array   -> controller must be one of the values
 *
 * Rules are applied in declaration order, so a field cleared by an earlier rule correctly
 * disables later rules that depend on it (nested display-logic groups).
 * The same rules drive step cleanup, submission cleanup and conditional required validation,
 * so values of hidden fields never leak into the preview or the PDF.
 */
final class FieldConditions
{
    /**
     * Clears every dependent field whose conditions are not met.
     *
     * @param array<string, mixed> $data
     * @param array<int, array<int, mixed>> $rules
     * @param bool $onlyResolvable skip rules whose controllers are not all present in $data
     *                             (per step: controllers may live in another step)
     * @return array<string, mixed>
     */
    public static function apply(array $data, array $rules, bool $onlyResolvable = false): array
    {
        foreach ($rules as $rule) {
            [$fields, $alternatives] = self::split($rule);

            if ($onlyResolvable && !self::isResolvable($data, $alternatives)) {
                continue;
            }

            if (self::matchesAny($data, $alternatives)) {
                continue;
            }

            foreach ($fields as $field) {
                if (array_key_exists($field, $data)) {
                    $data[$field] = null;
                }
            }
        }

        return $data;
    }

    /**
     * Whether a field is visible for the given data. Rules with unresolvable controllers are ignored.
     *
     * @param array<string, mixed> $data
     * @param array<int, array<int, mixed>> $rules
     */
    public static function isActive(string $field, array $data, array $rules): bool
    {
        $data = self::apply($data, $rules, true);

        foreach ($rules as $rule) {
            [$fields, $alternatives] = self::split($rule);

            if (!in_array($field, $fields, true) || !self::isResolvable($data, $alternatives)) {
                continue;
            }

            if (!self::matchesAny($data, $alternatives)) {
                return false;
            }
        }

        return true;
    }

    /**
     * All field names used by the rules (dependents and controllers).
     *
     * @param array<int, array<int, mixed>> $rules
     * @return array<int, string>
     */
    public static function referencedFields(array $rules): array
    {
        $fields = [];

        foreach ($rules as $rule) {
            [$dependents, $alternatives] = self::split($rule);
            array_push($fields, ...$dependents);

            foreach ($alternatives as $conditions) {
                array_push($fields, ...array_keys($conditions));
            }
        }

        return array_values(array_unique($fields));
    }

    /**
     * Clears the fields of a record whose conditions are not met (onBeforeWrite safety net).
     *
     * @param array<int, array<int, mixed>> $rules
     */
    public static function cleanRecord(object $record, array $rules): void
    {
        $data = [];
        foreach (self::referencedFields($rules) as $field) {
            $data[$field] = $record->getField($field);
        }

        foreach (self::apply($data, $rules) as $field => $value) {
            if ($value === null && $data[$field] !== null) {
                $record->setField($field, null);
            }
        }
    }

    /**
     * Checkbox / radio state for a stored value: $map = [stored value => PDF on-state].
     * Booleans are compared as "1" / "0".
     *
     * @param array<string|int, string> $map
     */
    public static function stateValue(object $record, string $fieldName, array $map): string
    {
        $value = $record->getField($fieldName);
        if (is_bool($value)) {
            $value = (int) $value;
        }

        return (string) ($map[(string) ($value ?? '')] ?? '');
    }

    /**
     * Rules of an entity model (e.g. SubmissionChild) with every field name prefixed,
     * as used by the repeated entity steps ("Child2" . "LastName").
     *
     * @param array<int, array<int, mixed>> $rules
     * @return array<int, array<int, mixed>>
     */
    public static function prefix(array $rules, string $prefix): array
    {
        $out = [];

        foreach ($rules as $rule) {
            [$fields, $alternatives] = self::split($rule);
            $prefixed = [array_map(static fn (string $field): string => $prefix . $field, $fields)];

            foreach ($alternatives as $conditions) {
                $set = [];
                foreach ($conditions as $controller => $expected) {
                    $set[$prefix . $controller] = $expected;
                }
                $prefixed[] = $set;
            }

            $out[] = $prefixed;
        }

        return $out;
    }

    /**
     * Data of one entity out of the merged step data: keys "<prefix><Field>" -> "<Field>".
     *
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    public static function unprefix(array $data, string $prefix): array
    {
        $out = [];

        foreach ($data as $key => $value) {
            $key = (string) $key;
            if (str_starts_with($key, $prefix) && strlen($key) > strlen($prefix)) {
                $out[substr($key, strlen($prefix))] = $value;
            }
        }

        return $out;
    }

    /**
     * @param array<string, mixed> $data
     * @param array<string, string|array<int, string>> $conditions
     */
    public static function matches(array $data, array $conditions): bool
    {
        foreach ($conditions as $controller => $expected) {
            $actual = self::normalize($data[$controller] ?? null);

            if (is_array($expected)) {
                if (!in_array($actual, array_map('strval', $expected), true)) {
                    return false;
                }
                continue;
            }

            if (str_starts_with($expected, '!')) {
                if ($actual === substr($expected, 1)) {
                    return false;
                }
                continue;
            }

            if ($actual !== $expected) {
                return false;
            }
        }

        return true;
    }

    /**
     * @param array<int, mixed> $rule
     * @return array{0: array<int, string>, 1: array<int, array<string, string|array<int, string>>>}
     */
    private static function split(array $rule): array
    {
        $fields = (array) array_shift($rule);

        return [$fields, array_values($rule)];
    }

    /**
     * @param array<string, mixed> $data
     * @param array<int, array<string, string|array<int, string>>> $alternatives
     */
    private static function matchesAny(array $data, array $alternatives): bool
    {
        foreach ($alternatives as $conditions) {
            if (self::matches($data, $conditions)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param array<string, mixed> $data
     * @param array<int, array<string, string|array<int, string>>> $alternatives
     */
    private static function isResolvable(array $data, array $alternatives): bool
    {
        foreach ($alternatives as $conditions) {
            foreach (array_keys($conditions) as $controller) {
                if (!array_key_exists($controller, $data)) {
                    return false;
                }
            }
        }

        return true;
    }

    private static function normalize(mixed $value): string
    {
        if ($value === null || $value === false) {
            return '';
        }

        if ($value === true) {
            return '1';
        }

        return is_scalar($value) ? trim((string) $value) : '';
    }
}
