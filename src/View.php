<?php

namespace Simplifying;


class View
{
    public static function render($content) {
        echo <<<HTML_HERE_DOC
        $content
HTML_HERE_DOC;
    }


    public static function node($content, $node, $times = 1, $classes = "") {
        $sumDiv = "";

        for($i = 1; $i <= $times; $i++) {
            if(is_callable($content)) {
                $sumDiv .=  "\n<$node class=$classes>\n   " . $content($i) . "\n</$node>";
            } else {
                $sumDiv .=  "\n<$node class=$classes>\n   $content\n</$node>";
            }
        }

        return $sumDiv;
    }


    public static function div($content, $times = 1, $classes = "") {
        return View::node($content, "div", $times, $classes);
    }


    public static function p($content, $times = 1, $classes = "") {
        return View::node($content, "p", $times, $classes);
    }
}