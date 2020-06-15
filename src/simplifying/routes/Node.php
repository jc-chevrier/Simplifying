<?php

namespace simplifying\routes;

/**
 * Classe Node.
 *
 * Noeud pour faire des arbres n-aires.
 *
 * @author CHEVRIER Jean-Christophe
 * @package simplifying\routes
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


    /**
     * Node constructor.
     * @param string $value
     */
    public function __construct(string $value) {
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
     *
     * @param string $value
     * @return mixed|null
     */
    public function searchNodeInChildNodes(string $value) {
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
     *
     * @param string $value
     * @return mixed|null
     */
    public function searchNode(string $value) {
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
     *
     * @return array
     */
    public function searchChildParameterNodes() : array {
        $childParameterNodes = [];
        foreach($this->childNodes as $index => $childNode) {
            if($childNode->type == NodeType::PARAMETER_NODE) {
                $childParameterNodes[] = $childNode;
            }
        }
        return $childParameterNodes;
    }


    /**
     * @return string
     */
    public function __toString() : string
    {
        return $this->toString();
    }

    /**
     * Afficher l'arborescence à partir d'un noeud.
     *
     * @param int $tabulationPx
     * @param int $indentation
     * @return string
     */
    public function toString(int $tabulationPx = 40, int $indentation = 0) : string {
        $string = "<div class='Node$indentation'>" . $this->type . "[" . $this->value . "]</div>";

        $indentation++;
        if(count($this->childNodes) != 0) {
            $string .= "<style> .Node$indentation{margin-left: " . ($tabulationPx * $indentation) ."px;} </style>";
        }
        foreach($this->childNodes as $index => $childNode) {
            $string .= $childNode->toString($tabulationPx, $indentation);
        }

        return $string;
    }



    /**
     * @param string $name
     * @return bool
     */
    public function __get(string $name) {
        if (isset($this->$name)) {
            return $this->$name;
        }
        return false;
    }

    /**
     * @param string $name
     * @param $value
     * @return bool
     */
    public function __set(string $name, $value) {
        if (isset($this->$name)) {
            $this->$name = $value;
        }
        return false;
    }
}