<?php

namespace simplifying\views;

/**
 * Classe Template.
 *
 * T <=> Template.
 *
 * regExp <=> regular expression
 *
 * @author CHEVRIER Jean-Christophe.
 */
class Template
{
    const regExpTNode = "<{2}\/{0,1}[a-zA-Z0-9-_ \.]+>{2}";

    private static $rootRelativePath = ".\\..\\..\\app\\views\\";
    private static $rootAbsolutePath;

    private $name;
    private $params;

    private $loopSequence;
    private $conditionSequence;



    public function __construct($name, $params = [])
    {
        $this->name = $name;
        $this->params = $params;

        $loopSequence = 0;
        $conditionSequence = 0;

        Template::initialiseRootAbsolutePath();
    }



    public static function rootRelativePath($rootRelativePath) {
        Template::$rootRelativePath = $rootRelativePath;
        Template::$rootAbsolutePath = null;
    }

    private static function initialiseRootAbsolutePath() {
        if(Template::$rootAbsolutePath == null) {
            if(Template::isRelativePath(Template::$rootRelativePath)) {
                Template::$rootAbsolutePath = Template::parseInAbsolutePath(Template::$rootRelativePath);
            } else {
                Template::$rootAbsolutePath = Template::$rootRelativePath;
            }
        }
    }

    private static function isRelativePath($path) {
        $dirs = explode('\\', $path);
        return array_search('.', $dirs) != false || array_search('..', $dirs) != false;
    }

    private static function parseInAbsolutePath($relativePath) {
        $dirs = explode('\\', $relativePath);
        $i = 0;
        while($i < count($dirs)) {
            $dir = $dirs[$i];
            if($dir == '.') {
                unset($dirs[$i]);
                $currentDir = explode('\\', __DIR__);
                $dirs = array_merge($currentDir, $dirs);
                $i += count($currentDir);
            } else {
                if($dir == '..') {
                    unset($dirs[$i]);
                    unset($dirs[$i - 1]);
                    $dirs = array_values($dirs);
                    $i--;
                } else {
                    $i++;
                }
            }
        }
        $absolutePath = implode('\\', $dirs);
        return $absolutePath;
    }



    public static function render($path, $params = []) {
        $template = new Template($path, $params);
        $template->_render();
    }

    public function _render() {
        $parsedContent = $this->parse();
        View::render($parsedContent);
    }



    private function getTAbsolutePath($TName) {
        return Template::$rootAbsolutePath . $TName . '.html';
    }

    private function getTContent($path) {
        $content = file_get_contents($path);
        if($content == false) {
            throw new \InvalidArgumentException('Le chargement du template a échoué !');
        }
        return $content;
    }



    private function getTHierarchy() {
        $TNames = [ $this->name ];
        $TPath = $this->getTAbsolutePath($this->name);
        $TPaths = [ $TPath ];
        $TContent = $this->getTContent($TPath);
        $TContents = [ $TContent ];

        $firstTNode = $this->nextTNodeAndItsContents($TContent);
        while($firstTNode != false && $firstTNode['TNodeLabel'] === TNodeLabel::PARENT) {
            $nameOfTParent = $firstTNode['otherContents'][0];
            $pathOfTParent = $this->getTAbsolutePath($nameOfTParent);
            $contentOfTParent = $this->getTContent($pathOfTParent);

            array_unshift($TNames, $nameOfTParent);
            array_unshift($TPaths, $pathOfTParent);
            array_unshift($TContents, $contentOfTParent);

            $firstTNode = $this->nextTNodeAndItsContents($contentOfTParent);
        }

        return [ 'TNames' => $TNames,
                 'TPaths' => $TPaths,
                 'TContents' => $TContents ];
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

    private function getTNodeContents($TNode) {
        return substr($TNode, 2, -2);
    }

    private function isEndTNode($label) {
        switch ($label) {
            case TNodeLabel::END_BLOCK :
            case TNodeLabel::END_CONDITION :
            case TNodeLabel::END_LOOP :
                return true;
            default:
                return false;
        }
    }

    private function isTNodeLabel($label) {
        switch ($label) {
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
        $hierarchy = $this->getTHierarchy();
        $TContents = $hierarchy['TContents'];

        //Parsing en arbre n-aire du template this et des templates parents si existant.
        $parsedTContent = "";
        foreach($TContents as $key => $TContent) {
            //$tree = parseInTree($content);
            $parsedTContent .= $TContent;
        }

        return $parsedTContent;
    }

    private function parseInTree($content) {

    }

    private function parseInHtml() {

    }
}