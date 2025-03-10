<?php

declare(strict_types=1);

namespace Mvc4us\Utils;

/**
 * @author erdem
 */
final class ArrayUtils
{
    /**
     * This class should not be instantiated.
     */
    private function __construct()
    {
    }

    /**
     * Improved version of PHP built-in function array_merge_recursive().
     * Modified to match `array_merge` behavior for numeric keys.
     *
     * @param array $array1
     * @param array $array2
     * @return array
     * @author Daniel <daniel (at) danielsmedegaardbuus (dot) dk>
     * @author Gabriel Sobrinho <gabriel (dot) sobrinho (at) gmail (dot) com>
     * @link   https://www.php.net/manual/en/function.array-merge-recursive.php#92195
     * @see    \array_merge()
     */
    public static function merge(array $array1, array $array2): array
    {
        $merged = $array1;

        foreach ($array2 as $key => $value) {
            if (is_array($value) && isset($merged[$key]) && is_array($merged[$key])) {
                $value = self::merge($merged[$key], $value);
            }
            if (is_string($key)) {
                $merged[$key] = $value;
            } else {
                $merged[] = $value;
            }
        }

        return $merged;
    }
}

