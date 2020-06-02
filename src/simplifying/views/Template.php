<?php

namespace simplifying\views;

/**
 * Classe Template.
 *
 * THierarchy <=> Template Hierarchy.
 * TNode <=> Template Node, noeud de nature <<...>>.
 * TVar <=> Template Variable.
 *
 * regExp <=> regular expression
 *
 * @author CHEVRIER Jean-Christophe.
 */
class Template
{
    const regExpTNode = "<{2}\/{0,1}[a-zA-Z0-9-_ \.]+>{2}";

    private static $rootPath;

    private $path;
    private $params;

    private $loopSequence;
    private $conditionSequence;



    public function __construct($path, $params = [])
    {
        $this->path = $path;
        $this->params = $params;

        $loopSequence = 0;
        $conditionSequence = 0;

        if(Template::$rootPath == null) {
            Template::buildRootPath();
        }
    }

    public static function render($path, $params = []) {
        $template = new Template($path, $params);
        $template->_render();
    }

    public function _render() {
        $parsedContent = $this->parse();
        View::render($parsedContent);
    }




    private function getAbsolutePath($path) {
        return Template::$rootPath . $path . '.html';
    }

    private function getContent($path) {
        $content = file_get_contents($path);
        if($content == false) {
            throw new \InvalidArgumentException('Le chargement du template a échoué !');
        }
        return $content;
    }



    private function getTHierarchy() {
        $path = $this->getAbsolutePath($this->path);
        $paths = [ $path ];
        $content = $this->getContent($path);
        $contents = [ $content ];

        $firstTNode = $this->nextTNodeAndItsContents($content);
        while($firstTNode != false && $firstTNode['TNodeLabel'] === TNodeLabel::PARENT) {
            $pathOfParent = $this->getAbsolutePath($firstTNode['otherContents'][0]);
            $contentOfParent = $this->getContent($pathOfParent);
            array_unshift($contents, $contentOfParent);
            array_unshift($paths, $pathOfParent);
            $firstTNode = $this->nextTNodeAndItsContents($contentOfParent);
        }

        return [ $paths, $contents ];
    }



    private function nextTNode($content) {
        $matches = [];
        $matchesFound = preg_match('/' . Template::regExpTNode . '/', $content, $matches);
        if($matchesFound) {
            return $matches[0];
        } else {
            return false;
        }
    }

    private function nextTNodeAndItsContents($content) {
        $nextTNode = $this->nextTNode($content);
        if($nextTNode == false) {
            return false;
        } else {
            $contentsStr = $this->getTNodeContents($nextTNode);
            $contentsArray = preg_split("/ +/", $contentsStr);
            if(count($contentsArray) == 0) {
                throw new \InvalidArgumentException("Noeud de template vide : $nextTNode !");
            } else {
                $TNodeStructure = [ 'TNode' => $nextTNode ];

                $aContent = strtolower(array_shift($contentsArray));
                if($this->isTNodeLabel($aContent)) {
                    $TNodeStructure['TNodeLabel'] = $aContent;
                    $TNodeStructure['isEndTNode'] = $this->isEndTNode($aContent);
                } else {
                    throw new \InvalidArgumentException("Noeud de template incorrect : $nextTNode !");
                }
                $TNodeStructure['otherContents'] = $contentsArray;
                return $TNodeStructure;
            }
        }
    }

    private function getTNodeContents($aTNode) {
        return substr($aTNode, 2, -2);
    }

    private function isEndTNode($word) {
        switch ($word) {
            case TNodeLabel::END_BLOCK :
            case TNodeLabel::END_CONDITION :
            case TNodeLabel::END_LOOP :
                return true;
            default:
                return false;
        }
    }

    private function isTNodeLabel($word) {
        switch ($word) {
            case TNodeLabel::VAL :
            case TNodeLabel::ROUTE :
            case TNodeLabel::PARENT:
            case TNodeLabel::BLOCK :
            case TNodeLabel::END_BLOCK :
            case TNodeLabel::CONDITION :
            case TNodeLabel::END_CONDITION :
            case TNodeLabel::LOOP :
            case TNodeLabel::END_LOOP :
                return true;
            default:
                return false;
        }
    }


    private function parse() {
        //Chargement de la hiérarchie de template.
        list($paths, $contents) = $this->getTHierarchy();

        //Parsing en arbre n-aire du template enfants et des templates parents.
        $parsedContent = "";
        foreach($contents as $key => $content) {
            //$tree = parseInTree($content);
            $parsedContent .= $content;
        }

        return $parsedContent;
    }

    private function parseInTree($content) {

    }

    private function parseInHtml() {

    }



    private static function buildRootPath() {
        $dirs = explode('\\', __DIR__);
        array_pop($dirs);
        array_pop($dirs);
        Template::$rootPath = implode('\\', $dirs) . "\\app\\views\\";
    }

    public static function rootPath($rootPath) {
        Template::$rootPath = $rootPath;
    }
}