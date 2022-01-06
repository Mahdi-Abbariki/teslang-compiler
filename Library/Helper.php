<?php

namespace Library;

class Helper
{
    public static function array_flatten($array = null, $depth = 1) {
        $result = [];
        if (!is_array($array)) $array = func_get_args();
        foreach ($array as $key => $value) {
            if (is_array($value) && $depth) {
                $result = array_merge($result, self::array_flatten($value, $depth - 1));
            } else {
                $result = array_merge($result, [$key => $value]);
            }
        }
        return $result;
    }
}