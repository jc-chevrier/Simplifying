<?php

namespace simplifying\templates;

class TNode
{
    private $parent;
    private $properties;
    private $children;
    private $nbChildren;



    /**
     * TNode constructor.
     * @param array $properties
     * @param array $children
     */
    public function __construct(array $properties = [],  array $children = []) {
        $this->properties = $properties;
        $this->children = [];
        $this->nbChildren = 0;
        foreach($children as $key => $child) {
            $this->addChild($child);
        }
    }

    /***
     * @return TNode
     */
    public static function getATNodeRoot() : TNode {
        $root = new TNode();
        $root->label = TNodeLabel::ROOT;
        return $root;
    }



    /**
     * @param $keyProperty
     * @param $valueProperty
     */
    public function addProperty(string $keyProperty, string $valueProperty) : void{
        $this->properties[$keyProperty] = $valueProperty;
    }

    /**
     * @param string $keyProperty
     */
    public function removeProperty(string $keyProperty) : void {
        unset($this->properties[$keyProperty]);
    }


    /**
     * @param TNode $child
     */
    public function addChild(TNode $child) : void {
        $child->parent = $this;
        $this->children[] = $child;
        $this->nbChildren++;
    }

    /**
     * @param TNode $child
     */
    public function removeChild(TNode $child) : void {
        foreach($this->children as $key => $aChild) {
            if($aChild->TNode === $child->TNode) {
                unset($aChild);
                $this->nbChildren--;
                return;
            }
        }
        throw new \InvalidArgumentException("TNode->removeChild() : noeud à supprimer introuvable !");
    }

    /**
     * @param TNode $oldChild
     * @param TNode $newChild
     */
    public function replaceChild(TNode $oldChild, TNode $newChild) : void {
        $nbChildren = $this->nbChildren;
        for($i = 0; $i < $nbChildren; $i++) {
            $child = $this->children[$i];
            if($oldChild->TNode == $child->TNode) {
                $this->children[$i] = $newChild;
                $newChild->parent = $this;
                return;
            }
        }
        throw new \InvalidArgumentException("TNode->replaceChild() : noeud à remplacer introuvable !");
    }


    /**
     * @param callable $predicateForSelection
     * @param callable $filterForResult
     * @return array
     */
    public function searchTNodes(callable $predicateForSelection, callable $filterForResult = null) : array {
        $searchedTNodes = $this->searchChildTNodes($predicateForSelection, $filterForResult);
        foreach($this->children as $key => $child) {
            $searchedChildTNodes = $child->searchChildTNodes($predicateForSelection, $filterForResult);
            $searchedTNodes = array_merge($searchedTNodes, $searchedChildTNodes);
        }
        return $searchedTNodes;
    }

    /**
     * @param callable $predicateForSelection
     * @param callable $filterForResult
     * @return array
     */
    public function searchChildTNodes(callable $predicateForSelection, callable $filterForResult = null) : array {
        $searchedChildTNodes = [];
        foreach($this->children as $key => $child) {
            if($predicateForSelection($child)) {
                $searchedChildTNodes[] = $filterForResult == null ? $child : $filterForResult($child);
            }
        }
        return $searchedChildTNodes;
    }



    /**
     * @return TNode
     */
    public function clone() : TNode {
        $cloneOfThis = $this->cloneProperties();
        $cloneOfThis->parent = $this->parent;
        $this->cloneChildren($cloneOfThis);
        return $cloneOfThis;
    }

    /**
     * @return TNode
     */
    private function cloneProperties() : TNode {
        $cloneOfThis = new TNode();
        $cloneOfThis->properties = (new \ArrayObject($this->properties))->getArrayCopy();
        return $cloneOfThis;
    }

    /**
     * @param TNode $cloneOfThis
     * @return TNode
     */
    private function cloneChildren(TNode $cloneOfThis) {
        foreach($this->children as $key => $child) {
            $childClone = $child->cloneProperties();
            $childClone->parent = $cloneOfThis;
            $cloneOfThis->addChild($childClone);
            $child->cloneChildren($childClone);
        }
    }



    /**
     * @return bool
     */
    public function hasChildren() : bool
    {
        return  $this->nbChildren != 0;
    }



    /**
     * @param $keyProperty
     * @param $valueProperty
     * @return bool
     */
    public function propertyIs($keyProperty, $valueProperty) : bool {
        return $this->$keyProperty == $valueProperty;
    }

    /**
     * @param $label
     * @return bool
     */
    public function labelIs($label) : bool {
        return $this->propertyIs('label', $label);
    }

    /**
     * @param $label
     * @return bool
     */
    public function is($label) : bool {
        return $this->labelIs($label);
    }


    /**
     * @param string $property
     * @param TNode $aTNode
     * @return bool
     */
    public function hasSamePropertyThat(string $property, TNode $aTNode) : bool {
        return $this->$property == $aTNode->$property;
    }

    /**
     * @param TNode $aTNode
     * @return bool
     */
    public function hasSameLabelThat(TNode $aTNode) : bool {
        return $this->hasSamePropertyThat('label', $aTNode);
    }

    /**
     * @param TNode $aTNode
     * @return bool
     */
    public function hasSameIdThat(TNode $aTNode) : bool {
        return $this->hasSamePropertyThat('id', $aTNode);
    }



    /**
     * @param TNode $TNode
     * @return bool
     */
    public function isComplementaryWith(TNode $TNode) : bool {
        return TNodeLabel::TNodeLabelsAreComplementary($this->label, $TNode->label);
    }



    /**
     * @param string $name
     * @return bool|mixed
     */
    public function __get(string $name) {
        if(isset($this->$name)) {
            return $this->$name;
        } else {
            if(array_key_exists($name, $this->properties)) {
                return $this->properties[$name];
            }
        }
        return false;
    }

    /**
     * @param string $name
     * @param $value
     */
    public function __set(string $name, $value) : void {
        if(isset($this->$name)) {
            $this->$name = $value;
        } else {
            if(array_key_exists($name, $this->properties)) {
                $this->properties[$name] = $value;
            } else {
                $this->addProperty($name, $value);
            }
        }
    }


    /**
     * @return string
     */
    public function __toString() : string
    {
       return $this->toString();
    }

    /**
     * @param callable|null $predicateForSelection
     * @param int $tabulationPx
     * @param int $indentation
     * @return string
     */
    public function toString(callable $predicateForSelection = null, int $tabulationPx = 40, int $indentation = 0) : string {
        $string = "";
        if($indentation == 0) {
            $string .= "
            <style>
                .keyProperty {
                    color: orange;
                }
                .valueProperty {
                    color: green;
                }
            </style>";
        }

        $string .= "<div class='TNode$indentation'>[ ";
        if(count($this->properties) != 0) {
            $keysProperties = array_keys($this->properties);
            foreach($keysProperties as $key => $keyProperty) {
                if($predicateForSelection == null || $predicateForSelection($keyProperty)) {
                    $property = $this->properties[$keyProperty];
                    $string .= "<span class='keyProperty'>$keyProperty</span>=<span class='valueProperty'>$property</span> | ";
                }
            }
        }
        if($predicateForSelection == null || $predicateForSelection("nbChildren")) {
            $string .= " <span class='keyProperty'>nbChildren</span>=<span class='valueProperty'>$this->nbChildren</span>";
        }
        $string .= " ]</div>";

        $indentation++;
        if($this->nbChildren != 0) {
            $string .= "<style> .TNode$indentation{margin-left: " . ($tabulationPx * $indentation) ."px;} </style>";
        }
        foreach($this->children as $index => $child) {
            $string .= $child->toString($predicateForSelection, $tabulationPx, $indentation);
        }

        return $string;
    }
}