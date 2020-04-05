<?php

namespace simplifying\routes;

class ParameterNode extends Node {
    public function type() {
        return NodeType::PARAMETER_NODE;
    }
}