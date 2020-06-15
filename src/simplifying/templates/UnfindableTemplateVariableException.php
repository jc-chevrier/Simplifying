<?php

namespace simplifying\templates;

use Throwable;

/**
 * Classe SyntaxException.
 *
 * @author CHEVRIER Jean-Christophe
 * @package simplifying\templates
 */
class UnfindableTemplateVariableException extends \Exception
{
    public function __construct($message = "", $code = 0, Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}