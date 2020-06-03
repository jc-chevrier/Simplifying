<?php

namespace simplifying\templates;

class TNode
{
    private $properties;
    private $children;



    public function __construct($properties = [],  $children = []) {
        $this->properties = $properties;
        $this->children = $children;
    }



    public function addProperty($keyProperty, $valueProperty) {
        $this->properties[$keyProperty] = $valueProperty;
    }

    public function addChild($child) {
        $this->children[] = $child;
    }



    public function __get($name)  {
        if(isset($this->$name)) {
            return $this->$name;
        } else {
            if(isset($this->properties[$name])) {
                return $this->properties[$name];
            }
        }
    }
}