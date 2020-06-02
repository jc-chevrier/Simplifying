<?php

namespace simplifying\views;

/**
 * Classe View.
 *
 * @author CHEVRIER Jean-Christophe.
 */
class View
{
    /**
     * Envoyer du code html via un document virtuel (HEREDOC).
     */
    public static function render($content)
    {
        echo <<<HTML_HERE_DOC
        $content
HTML_HERE_DOC;
    }
}