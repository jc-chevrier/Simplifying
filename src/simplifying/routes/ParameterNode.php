<?php

namespace simplifying\routes;

/**
 * Classe ParameterNode.
 *
 * @author CHEVRIER Jean-Christophe
 * @package simplifying\routes
 */
class ParameterNode extends Node {
    /**
     * ParameterNode constructor.
     * @param $value
     */
    public function __construct($value) {
       parent::__construct($value);
       $this->type = NodeType::PARAMETER_NODE;
    }
}