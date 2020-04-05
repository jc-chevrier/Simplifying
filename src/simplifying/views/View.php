<?php

namespace simplifying\views;

/**
 * Classe View.
 */
class View
{
    /**
     * Envoyer du code html via un document virtuel (HEREDOC).
     */
    public static function render($content) {
        echo <<<HTML_HERE_DOC
        $content
HTML_HERE_DOC;
    }


    /**
     * Créer des noeuds.
     */
    public static function node($node, $content, $times, $classes) {
        $sumNodes = "";

        $startNode = "\n<$node class=$classes>\n";
        $endNode = "\n</$node>";

        for($i = 1; $i <= $times; $i++) {
            if(is_callable($content)) {
                $sumNodes .= $startNode . $content($i) . $endNode;
            } else {
                if(is_array($content)) {
                    $sumNodes .=  $startNode . $content[$i] . $endNode;
                } else {
                    $sumNodes .=  $startNode . $content . $endNode;
                }
            }
        }

        return $sumNodes;
    }


    /**
     * Créer des neouds <div>.
     */
    public static function div($content, $times = 1, $classes = "") {
        return View::node("div", $content, $times, $classes);
    }
}