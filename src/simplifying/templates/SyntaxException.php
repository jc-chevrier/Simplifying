<?php

namespace simplifying\templates;

use Throwable;

/**
 * Classe SyntaxException.
 *
 * @author CHEVRIER Jean-Christophe.
 */
class SyntaxException extends \Exception
{
    public function __construct($message = "", $code = 0, Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}