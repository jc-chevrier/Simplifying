<?php

namespace simplifying\templates;

use Throwable;

/**
 * Classe TSyntaxException.
 *
 * T <=> Template.
 *
 * @author CHEVRIER Jean-Christophe
 * @package simplifying\templates
 */
class TSyntaxException extends \Exception
{
    public function __construct($message = "", $code = 0, Throwable $previous = null)
    {
        $overMessage = "Erreur de syntaxe détectée au cours du parsing d'un template ! ";
        parent::__construct($overMessage.$message, $code, $previous);
    }
}