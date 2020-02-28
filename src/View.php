<?php

namespace Simplifying;


class View
{
    public static function render($content) {
        echo <<<HTML_HERE_DOC
        $content
HTML_HERE_DOC;
    }


    private static function node($node, $content, $times, $classes) {
        $sumNodes= "";

        for($i = 1; $i <= $times; $i++) {
            if(is_callable($content)) {
                $sumNodes .=  "\n<$node class=$classes>\n   " . $content($i) . "\n</$node>";
            } else {
                $sumNodes .=  "\n<$node class=$classes>\n   $content\n</$node>";
            }
        }

        return $sumNodes;
    }


    public static function div($content, $times = 1, $classes = "") {
        return View::node("div", $content, $times, $classes);
    }


    public static function p($content, $times = 1, $classes = "") {
        return View::node("p", $content, $times, $classes);
    }
}