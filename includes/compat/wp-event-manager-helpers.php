<?php

// phpcs:ignoreFile
/**
 * Compatibility helpers for legacy wp-event-manager functions.
 * Prevents fatal errors caused by missing helper definitions during strict mode bootstrap.
 */

defined('ABSPATH') || exit;

if (! function_exists('wem_safe_array')) {
    /**
     * Ensure a value is always returned as an array.
     */
    function wem_safe_array($value, array $fallback = []): array
    {
        if (is_array($value)) {
            return $value;
        }

        if ($value instanceof \Traversable) {
            return iterator_to_array($value);
        }

        if (empty($value)) {
            return $fallback;
        }

        return (array) $value;
    }
}

if (! function_exists('wem_safe_strlen')) {
    /**
     * Multibyte-safe string length.
     */
    function wem_safe_strlen($string): int
    {
        if (function_exists('mb_strlen')) {
            return mb_strlen((string) $string, 'UTF-8');
        }

        return strlen((string) $string);
    }
}

if (! function_exists('wem_safe_substr')) {
    /**
     * Multibyte-safe substring helper.
     */
    function wem_safe_substr($string, int $start, ?int $length = null): string
    {
        if (function_exists('mb_substr')) {
            return mb_substr((string) $string, $start, $length, 'UTF-8');
        }

        return substr((string) $string, $start, $length ?? strlen((string) $string));
    }
}

if (! function_exists('wem_is_truthy')) {
    /**
     * Normalize truthy checks from wp-event-manager helper set.
     */
    function wem_is_truthy($value): bool
    {
        if (is_bool($value)) {
            return $value;
        }

        $value = strtolower(trim((string) $value));

        return in_array($value, [ '1', 'true', 'yes', 'on', 'y' ], true);
    }
}

if (! function_exists('wem_array_get')) {
    /**
     * Safe array getter with dot notation support.
     */
    function wem_array_get($array, string $key, $default = null)
    {
        if (! is_array($array)) {
            return $default;
        }

        if (array_key_exists($key, $array)) {
            return $array[ $key ];
        }

        if (strpos($key, '.') === false) {
            return $default;
        }

        $segments = explode('.', $key);
        $value    = $array;

        foreach ($segments as $segment) {
            if (! is_array($value) || ! array_key_exists($segment, $value)) {
                return $default;
            }
            $value = $value[ $segment ];
        }

        return $value;
    }
}//end if
