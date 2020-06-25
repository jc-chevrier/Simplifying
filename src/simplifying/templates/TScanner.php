<?php

namespace simplifying\templates;

/**
 * Classe TScanner.
 *
 * Cette classe est un itérateur / scanner permettant d'itérer
 * sur les noeuds de template des templates. C'est un outil indispensable
 * à l'analyseur.
 *
 * T <=> Template.
 *
 * @author CHEVRIER Jean-Christophe
 * @package simplifying\templates
 */
class TScanner implements \Iterator {
    /**
     * Expression régulière des noeuds de template.
     */
    const REG_EXP_T_NODE = "<{2} *\/{0,1}[a-zA-Z]+ *[^<>]* *>{1}";
    /**
     * @var string     Chemin absolu du template.
     */
    private $TPath;
    /**
     * @var string      Contenu du template.
     */
    private $TContent;
    /**
     * @var int         Taille du contenu du template.
     */
    private $TContentLength;
    /**
     * @var int         La position courante dans le contenu.
     */
    private $offset;
    /**
     * @var string      Noeud courant
     */
    private $currentTNode;
    /**
     * @var int         Sequence des clés.
     */
    private $sequence;



    /**
     * TScanner constructor.
     * @param string $TPath
     */
    public function __construct(string $TPath) {
        $this->TPath = $TPath;
        $this->TContent = $this->getTContent($this->TPath);
        $this->TContentLength = strlen($this->TContent);
        $this->rewind();
    }



    /**
     * @param callable $action
     * @param $acc
     */
    public function forEach(callable $action, &$acc = null) : void {
        foreach ($this as $key => $TNode) {
           $action($TNode, $key, $acc);
        }
    }



    /**
     * @param string $path
     * @return string
     */
    private function getTContent(string $path) : string {
        $TContent = file_get_contents($path);
        if($TContent == false) {
            throw new \InvalidArgumentException('TScanner->getTContent() : chargement du template a échoué !');
        }
        return $TContent;
    }


    /*
     * Return the current element
     * @link https://php.net/manual/en/iterator.current.php
     * @return mixed Can return any type.
     * @since 5.0.0
     */
    public function current() {
        return $this->currentTNode;
    }

    /**
     * Move forward to next element
     * @link https://php.net/manual/en/iterator.next.php
     * @return void Any returned value is ignored.
     * @since 5.0.0
     */
    public function next() {
        $this->sequence++;
        $matches = [];
        $matchesFound = preg_match("/".TScanner::REG_EXP_T_NODE."/sm", $this->TContent, $matches, PREG_OFFSET_CAPTURE, $this->offset);
        if($matchesFound) {
            $nextOffset = $matches[0][1];
            $nextTNode = $matches[0][0];
            //Détection d'un noeud de template.
            if($nextOffset == $this->offset) {
                $this->currentTNode = ["TNode" => $nextTNode, "isIgnoredTNode" => false];
                $this->offset += strlen($nextTNode);
            //Détection d'un noeud de template de type IGNORED.
            } else {
                $nextTNode = substr($this->TContent, $this->offset, -$this->TContentLength+$nextOffset);
                $this->currentTNode = ["TNode" => $nextTNode, "isIgnoredTNode" => true];
                $this->offset = $nextOffset;
            }
        } else {
            if($this->offset < $this->TContentLength) {
                $nextTNode = substr($this->TContent, $this->offset);
                $this->currentTNode = ["TNode" => $nextTNode, "isIgnoredTNode" => true];
                $this->offset = $this->TContentLength;
            } else {
                $this->currentTNode = null;
            }
        }
    }

    /**
     * Return the key of the current element
     * @link https://php.net/manual/en/iterator.key.php
     * @return mixed scalar on success, or null on failure.
     * @since 5.0.0
     */
    public function key() {
        return $this->sequence;
    }

    /**
     * Checks if current position is valid
     * @link https://php.net/manual/en/iterator.valid.php
     * @return boolean The return value will be casted to boolean and then evaluated.
     * Returns true on success or false on failure.
     * @since 5.0.0
     */
    public function valid() {
        return $this->currentTNode !== null;
    }

    /**
     * Rewind the Iterator to the first element
     * @link https://php.net/manual/en/iterator.rewind.php
     * @return void Any returned value is ignored.
     * @since 5.0.0
     */
    public function rewind() {
        $this->sequence = -1;
        $this->offset = 0;
        $this->next();
    }
}