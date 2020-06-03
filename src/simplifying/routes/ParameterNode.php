<?php

namespace simplifying\routes;

/**
 * Classe ParameterNode.
 *
 * @author CHEVRIER Jean-Christophe.
 */
class ParameterNode extends Node {
    public function __construct($value) {
       parent::__construct($value);
       $this->type = NodeType::PARAMETER_NODE;
    }
}