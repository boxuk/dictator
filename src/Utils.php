<?php

declare(strict_types=1);

namespace BoxUk\Dictator;

class Utils
{
    /**
     * Recursive difference of an array
     *
     * @see https://gist.github.com/vincenzodibiaggio/5965342
     *
     * @param array $array1 First array.
     * @param array $array2 Second array.
     * @return array
     */
    public static function arrayDiffRecursive(array $array1, array $array2): array
    {
        $ret = [];

        foreach ($array1 as $key => $value) {
            if (array_key_exists($key, $array2)) {
                if (is_array($value)) {
                    $recursiveDiff = self::arrayDiffRecursive($value, $array2[$key]);

                    if (count($recursiveDiff)) {
                        $ret[$key] = $recursiveDiff;
                    }
                } elseif ($value !== $array2[$key]) {
                    $ret[$key] = $value;
                }
            } else {
                $ret[$key] = $value;
            }
        }

        return $ret;
    }

    /**
     * Whether this is an associative array
     *
     * @param array $array Array to check.
     * @return bool
     */
    public static function isAssocArray(array $array): bool
    {
        return ! array_is_list($array);
    }
}
