<?php

namespace simplifying\templates;

/**
 * Classe View.
 *
 * @author CHEVRIER Jean-Christophe
 * @package simplifying\templates
 */
class View
{
    /**
     * Envoyer du code html via un document virtuel (HEREDOC).
     */
    public static function render($content)
    {
        echo <<<VIEW_HERE_DOC
        $content
VIEW_HERE_DOC;
    }
}