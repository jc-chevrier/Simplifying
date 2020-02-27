<?php

namespace Simplifying;


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
        foreach($array as $element) {
            $callBack($element, $acc);
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