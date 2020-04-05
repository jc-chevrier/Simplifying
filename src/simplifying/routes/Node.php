<?php

namespace simplifying\routes;

/**
 * Classe Node.
 *
 * Noeud pour faire des arbres n-aires.
 */
class Node
{
    private $value;
    private $childNodes;




    public function __construct($value) {
        $this->value = $value;
        $this->childNodes = [];
    }



    /**
     * Ajouter un noeud enfant.
     *
     * @param $childNode -> un noeud ou une valeur.
     */
    public function addChild($childNode) {
        if(is_string($childNode)) {
            $this->childNodes[] = new Node($childNode);
        } else {
            $this->childNodes[] = $childNode;
        }
    }



    /**
     * Type du noeud.
     */
    public function type() {
        return NodeType::NODE;
    }




    /**
     * Récupérer le noeud de valeur $value.
     *
     * La recherche est effectuée dans le niveau
     * des noeuds enfants uniquement.
     */
    public function searchNodeInChildNodes($value) {
        foreach($this->childNodes as $index => $childNode) {
            if($childNode->value == $value) {
                return $childNode;
            }
        }
        return null;
    }

    /**
     * Récupérer le noeud de valeur $value.
     *
     * La recherche est effectuée dans tous les sous-niveaux.
     */
    public function searchNode($value) {
        //On cherche dans les noeuds enfants (trivial).
       $node = $this->searchNodeInChildNodes($value);
       if($node != null) {
           return $node;
       }

        //On cherche dans les niveaux en dessous des noeuds enfants (récursif).
        foreach($this->childNodes as $index => $childNode) {
            $node = $childNode->searchNode($value);
            if($node != null) {
                return $node;
            }
        }

        //On a pas trouvé.
       return null;
    }

    /**
     * Récupérer les noeuds enfants de type ParameterNode.
     */
    public function searchChildParameterNodes() {
        $childParameterNodes = [];
        foreach($this->childNodes as $index => $childNode) {
            if($childNode->type() == NodeType::PARAMETER_NODE) {
                $childParameterNodes[] = $childNode;
            }
        }
        return $childParameterNodes;
    }



    /**
     * Afficher l'arborescence à partir d'un noeud.
     */
    public function toString($indentation = 0, $tabulation = "||>>>") {
        $string = "";

        $string .= "<br>";
        for($i = 0; $i < $indentation; $i++) {
            $string .= $tabulation;
        }
        $string .= $this->type() . "[" . $this->value . "]";

        $indentation++;
        foreach($this->childNodes as $index => $childNode) {
            $string .= $childNode->toString($indentation);
        }

        return $string;
    }



    public function __get($name) {
        if (isset($this->$name)) {
            return $this->$name;
        }
        return false;
    }
}