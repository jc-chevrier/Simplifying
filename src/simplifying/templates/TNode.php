<?php

namespace simplifying\templates;

class TNode
{
    private $parent;
    private $properties;
    private $children;



    /**
     * TNode constructor.
     * @param array $properties
     * @param array $children
     */
    public function __construct(array $properties = [],  array $children = []) {
        $this->properties = $properties;
        $this->children = [];
        foreach($children as $key => $child) {
            $this->addChild($child);
        }
    }



    /**
     * @param $keyProperty
     * @param $valueProperty
     */
    public function addProperty($keyProperty, $valueProperty) {
        $this->properties[$keyProperty] = $valueProperty;
    }



    /**
     * @param TNode $child
     */
    public function addChild($child) {
        $child->parent = $this;
        $this->children[] = $child;
    }

    /**
     * @param TNode $child
     */
    public function removeChild($child) {
        foreach($this->children as $key => $aChild) {
            if($aChild == $child) {
                unset($aChild);
                break;
            }
        }
        throw new \InvalidArgumentException("TNode->removeChild() : noeud à supprimer introuvable !");
    }

    /**
     * @param TNode $oldChild
     * @param TNode $newChild
     */
    public function replaceChild(TNode $oldChild, TNode $newChild) : void {
        $nbChildren = count($this->children);
        for($i = 0; $i < $nbChildren; $i++) {
            $child = $this->children[$i];
            if($oldChild == $child) {
                $this->children[$i] = $newChild;
                break;
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
        if($predicateForSelection($this)) {
            $searchedChildTNodes[] = $filterForResult == null ? $this : $filterForResult($this);
        }
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
    private function cloneChildren(TNode $cloneOfThis) : TNode {
        foreach($this->children as $key => $child) {
            $childClone = $child->cloneProperties();
            $childClone->parent = $cloneOfThis;
            $cloneOfThis->addChild($childClone);
            $child->cloneChildren($childClone);
        }
    }


    /**
     * @param string $property
     * @param TNode $aTNode
     * @return bool
     */
    public function hasSameProperty(string $property, TNode $aTNode) : bool {
        return $this->$property == $aTNode->$property;
    }

    /**
     * @param TNode $aTNode
     * @return bool
     */
    public function hasSameLabel(TNode $aTNode) : bool {
        return $this->hasSameProperty('label', $aTNode);
    }

    /**
     * @param TNode $aTNode
     * @return bool
     */
    public function hasSameId(TNode $aTNode) : bool {
        return $this->hasSameProperty('id', $aTNode);
    }



    /**
     * @param string $name
     * @return bool|mixed
     */
    public function __get(string $name) {
        if(isset($this->$name)) {
            return $this->$name;
        } else {
            if(isset($this->properties[$name])) {
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
            if(isset($this->properties[$name])) {
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
     * @param string $tabulation
     * @param int $indentation
     * @return string
     */
    public function toString(string $tabulation = "||>>>", int $indentation = 0) : string {
        $string = "";

        $string .= "<br>";
        for($i = 0; $i < $indentation; $i++) {
            $string .= $tabulation;
        }

        $string .= "[ ";
        if(count($this->properties) != 0) {
            $keysProperties = array_keys($this->properties);
            $keyLastProperty = array_pop($keysProperties);
            foreach($keysProperties as $key => $keyProperty) {
                $property = $this->properties[$keyProperty];
                $string .= "$keyProperty=$property | ";
            }
            $string .= "$keyLastProperty=". $this->properties[$keyLastProperty] . " | ";
        }
        $string .= " nbChildren=" . count($this->children) . " ]";

        $indentation++;
        foreach($this->children as $index => $child) {
            $string .= $child->toString($tabulation, $indentation);
        }

        return $string;
    }
}