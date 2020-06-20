<?php

namespace simplifying\templates;

use Throwable;

/**
 * Classe TVariableException.
 *
 * T <=> Template.
 *
 * @author CHEVRIER Jean-Christophe
 * @package simplifying\templates
 */
class TVariableException extends \Exception
{
    public function __construct($message = "", $code = 0, Throwable $previous = null)
    {
        $overMessage = "Erreur avec une variable détectée au cours du parsing d'un template ! ";
        parent::__construct($overMessage.$message, $code, $previous);
    }
}