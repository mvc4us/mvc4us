<?php

declare(strict_types=1);

namespace Mvc4us\Utils;

use Ouzo\Utilities\Arrays;

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
     * Improved version of PHP built-in function `array_merge_recursive()`.
     * Modified to match `array_merge()` behavior for numeric keys.
     *
     * @param array ...$arrays
     * @return array
     * @author Daniel <daniel (at) danielsmedegaardbuus (dot) dk>
     * @author Gabriel Sobrinho <gabriel (dot) sobrinho (at) gmail (dot) com>
     * @link   https://www.php.net/manual/en/function.array-merge-recursive.php#89684
     * @link   https://www.php.net/manual/en/function.array-merge-recursive.php#92195
     * @see    \array_merge()
     * @see    \array_merge_recursive()
     */
    public static function merge(array ...$arrays): array
    {
        $merged = array_shift($arrays) ?? [];

        while (null !== $array = array_shift($arrays)) {
            foreach ($array as $key => $value) {
                if (is_array($value) && isset($merged[$key]) && is_array($merged[$key])) {
                    $value = self::merge($merged[$key], $value);
                }
                if (is_string($key)) {
                    $merged[$key] = $value;
                } else {
                    $merged[] = $value;
                }
            }
        }
        return $merged;
    }

    /**
     * Wrapper for {@see \Ouzo\Utilities\Arrays::getNestedValue()} to accept dot notation for the path.
     *
     * @link https://ouzo.readthedocs.io/en/latest/utils/arrays.html#getnestedvalue Ouzo Goodies - Arrays
     */
    public static function getNestedValue(array $array, array|string $path, string $separator = '.'): mixed
    {
        if (is_array($path)) {
            $path = implode($separator, $path);
        }
        $path = explode($separator, $path);
        return Arrays::getNestedValue($array, $path);
    }

    /**
     * Wrapper for {@see \Ouzo\Utilities\Arrays::setNestedValue()} to accept dot notation for the path.
     *
     * @link https://ouzo.readthedocs.io/en/latest/utils/arrays.html#setnestedvalue Ouzo Goodies - Arrays
     */
    public static function setNestedValue(
        array &$array,
        array|string $path,
        mixed $value,
        string $separator = '.'
    ): void {
        if (is_array($path)) {
            $path = implode($separator, $path);
        }
        $path = explode($separator, $path);
        Arrays::setNestedValue($array, $path, $value);
    }

    /**
     * Wrapper for {@see \Ouzo\Utilities\Arrays::removeNestedKey()} to accept dot notation for the path.
     *
     * @link https://ouzo.readthedocs.io/en/latest/utils/arrays.html#removenestedkey Ouzo Goodies - Arrays
     */
    public static function removeNestedKey(array &$array, array|string $path, string $separator = '.'): void
    {
        if (is_array($path)) {
            $path = implode($separator, $path);
        }
        $path = explode($separator, $path);
        Arrays::removeNestedKey($array, $path);
    }

    /**
     * Variadic version of {@link https://github.com/gajus/marray Marray diff_key_recursive()}.
     *
     * @param array $array
     * @param array ...$arrays
     * @return array
     * @link https://github.com/gajus/marray/blob/d93218863be26848b502094d29c803a02c6457e2/src/marray.php#L78
     */
    public static function diffKeyRecursive(array $array, array ...$arrays): array
    {
        $diff = $array;
        while (null !== $array2 = array_shift($arrays)) {
            $diff = array_diff_key($array, $array2);
            $intersect = array_intersect_key($array, $array2);

            foreach ($intersect as $k => $v) {
                if (is_array($array[$k]) && is_array($array2[$k])) {
                    $d = self::diffKeyRecursive($array[$k], $array2[$k]);

                    if ($d) {
                        $diff[$k] = $d;
                    }
                }
            }
        }
        return $diff;
    }
}
