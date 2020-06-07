<?php

namespace simplifying\templates;

use Throwable;

/**
 * Classe SyntaxException.
 *
 * @author CHEVRIER Jean-Christophe.
 */
class TemplateSyntaxException extends \Exception
{
    public function __construct($message = "", $code = 0, Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}