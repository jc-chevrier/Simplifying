<?php

namespace simplifying\routes;

/**
 * Classe ParameterNode.
 *
 * @author CHEVRIER Jean-Christophe
 * @package simplifying\routes
 */
class ParameterUriNode extends UriNode {
    /**
     * ParameterNode constructor.
     * @param $value
     */
    public function __construct($value) {
       parent::__construct($value);
       $this->type = UriNodeType::PARAMETER_URI_NODE;
    }
}