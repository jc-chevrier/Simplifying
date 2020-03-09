<?php

namespace simplifying;

class Util
{
    public static function toString($array) {
        $string = "";
        foreach($array as $element) {
            $string .= $element;
        }
        return $string;
    }



    public static function each($array, $callBack) {
        $acc = null;
        $endIndex = count($array);
        for($i = 0; $i < $endIndex; $i++) {
            $element = $array[$i];
            $acc = $callBack($element, $i, $acc, $array);
        }
        return $acc;
    }



    public static function eachDec($array, $callBack) {
        $acc = null;
        $startIndex = count($array) - 1;
        for($i = $startIndex; $i >= 0; $i--) {
            $element = $array[$i];
            $acc = $callBack($element, $i, $acc, $array);
        }
        return $acc;
    }



    public static function removeOccurrences($search, $string) {
        if(is_array($search)) {
            foreach($search as $element) {
                $string = str_replace($element, "", $string);
            }
            return $string;
        } else {
            $string = str_replace($search, "", $string);
            return $string;
        }
    }
}