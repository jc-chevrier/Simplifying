<?php

namespace simplifying\routes;

/**
 * Classe Node.
 *
 * Noeud pour faire des arbres n-aires.
 *
 * @author CHEVRIER Jean-Christophe.
 */
class Node
{
    /**
     * La valeur d'un noeud.
     *
     * Ici une partie d'URI.
     */
    private $value;
    /**
     * Noeuds enfants.
     */
    private $childNodes;
    /**
     * Type du noeud.
     */
    private $type;




    public function __construct($value) {
        $this->value = $value;
        $this->childNodes = [];
        $this->type = NodeType::NODE;
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
     * Rechercher le noeud de valeur $value.
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
     * Rechercher le noeud de valeur $value.
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
     * Rechercher les noeuds enfants de type ParameterNode.
     *
     * La recherche est effectuée dans le niveau
     * des noeuds enfants uniquement.
     */
    public function searchChildParameterNodes() {
        $childParameterNodes = [];
        foreach($this->childNodes as $index => $childNode) {
            if($childNode->type == NodeType::PARAMETER_NODE) {
                $childParameterNodes[] = $childNode;
            }
        }
        return $childParameterNodes;
    }




    /**
     * Afficher l'arborescence à partir d'un noeud.
     */
    public function toString($tabulation = "||>>>", $indentation = 0) {
        $string = "";

        $string .= "<br>";
        for($i = 0; $i < $indentation; $i++) {
            $string .= $tabulation;
        }
        $string .= $this->type . "[" . $this->value . "]";

        $indentation++;
        foreach($this->childNodes as $index => $childNode) {
            $string .= $childNode->toString($tabulation, $indentation);
        }

        return $string;
    }




    public function __get($name) {
        if (isset($this->$name)) {
            return $this->$name;
        }
        return false;
    }

    public function __set($name, $value) {
        if (isset($this->$name)) {
            $this->$name = $value;
        }
        return false;
    }
}