<?php

namespace simplifying\templates;

/**
 * Classe Template.
 *
 * T <=> Template.
 *
 * regExp <=> regular expression.
 *
 * @author CHEVRIER Jean-Christophe.
 */
class Template
{
    const regExpTNode = "<{2} *\/{0,1}[a-zA-Z]+ *[a-zA-Z0-9-_ \.:]+>{2}";

    private static $rootRelativePath = ".\\..\\..\\app\\views\\";
    private static $rootAbsolutePath;

    private $name;

    private $externalParameters;
    private $internalValues;



    public function __construct($name, $externalParameters = [])
    {
        $this->name = $name;

        $this->externalParameters = $externalParameters;
        $this->internalValues = [];

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
        $dir = $dirs[$i];
        if($dir == '.') {
            unset($dirs[$i]);
            $currentDir = explode('\\', __DIR__);
            $dirs = array_merge($currentDir, $dirs);
            $i += count($currentDir);
        }
        while($i < count($dirs)) {
            $dir = $dirs[$i];
            if($dir == '..') {
                unset($dirs[$i]);
                unset($dirs[$i - 1]);
                $dirs = array_values($dirs);
                $i--;
            } else {
                $i++;
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
        $TContent = file_get_contents($path);
        if($TContent == false) {
            throw new \InvalidArgumentException('Le chargement du template a échoué !');
        }
        return $TContent;
    }



    private function getTHierarchy() {
        $TNames = [ $this->name ];
        $TPath = $this->getTAbsolutePath($this->name);
        $TPaths = [ $TPath ];
        $TContent = $this->getTContent($TPath);
        $TContents = [ $TContent ];

        $TNodeParent = $this->nextTNode($TContent);
        while($TNodeParent != false && $TNodeParent->label === TNodeLabel::PARENT) {
            $pathOfTParent = $this->getTAbsolutePath($TNodeParent->name);
            $contentOfTParent = $this->getTContent($pathOfTParent);

            array_unshift($TNames, $TNodeParent->name);
            array_unshift($TPaths, $pathOfTParent);
            array_unshift($TContents, $contentOfTParent);

            $TNodeParent = $this->nextTNode($contentOfTParent);
        }

        return [ 'TNames' => $TNames,
                 'TPaths' => $TPaths,
                 'TContents' => $TContents ];
    }



    private function nextSimpleTNode($content) {
        $matches = [];
        $matchesFound = preg_match('/' . Template::regExpTNode . '/', $content, $matches);
        if($matchesFound) {
            return $matches[0];
        } else {
            return false;
        }
    }

    private function nextTNode($content) {
        $nextTNode = $this->nextSimpleTNode($content);
        if($nextTNode == false) {
            return false;
        } else {
            $contentsStr = $this->getSimpleTNodeContents($nextTNode);
            $contentsArray = preg_split("/ +/", $contentsStr, -1, PREG_SPLIT_NO_EMPTY);

            if(count($contentsArray) == 0) {
                throw new \InvalidArgumentException("Noeud de template vide : $nextTNode !");
            } else {
                $TNodeStructure = [ 'TNode' => $nextTNode ];

                $aContent = strtolower(array_shift($contentsArray));
                if($this->isTNodeLabel($aContent)) {
                    $TNodeStructure['label'] = $aContent;
                } else {
                    throw new SyntaxException("Template->nextTNode() : noeud de template incorrect : $nextTNode !");
                }
                $TNodeStructure['otherContents'] = $contentsArray;

                switch ($TNodeStructure['label']) {
                    case TNodeLabel::VALUE :
                        $nextTNode = $this->getTNode2Contents($TNodeStructure);
                        break;
                    case TNodeLabel::ROUTE :
                        $nextTNode = $this->getTNodeRoute($TNodeStructure);
                        break;
                    case TNodeLabel::PARENT :
                        $nextTNode = $this->getTNode2Contents($TNodeStructure);
                        break;
                    case TNodeLabel::ABSTRACT_BLOCK :
                        $nextTNode = $this->getTNode2Contents($TNodeStructure);
                        break;
                    case TNodeLabel::BLOCK :
                        $nextTNode = $this->getTNode2Contents($TNodeStructure);
                        break;
                    case TNodeLabel::END_BLOCK :
                        $nextTNode = $this->getTNode1Content($TNodeStructure);
                        break;
                    case TNodeLabel::CONDITION :
                        //$nextTNode = ;
                        break;
                    case TNodeLabel::END_CONDITION :
                        $nextTNode = $this->getTNode1Content($TNodeStructure);
                        break;
                    case TNodeLabel::LOOP :
                        //$nextTNode = ;
                        break;
                    case TNodeLabel::END_LOOP :
                        $nextTNode = $this->getTNode1Content($TNodeStructure);
                        break;
                }

                return $nextTNode;
            }
        }
    }

    private function getSimpleTNodeContents($TNode) {
        return substr($TNode, 2, -2);
    }

    private function isTNodeLabel($label) {
        switch ($label) {
            case TNodeLabel::VALUE :
            case TNodeLabel::ROUTE :
            case TNodeLabel::PARENT :
            case TNodeLabel::ABSTRACT_BLOCK :
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

    public function getTNode1Content($TNodeStructure) {
        $nbOtherContents = count($TNodeStructure['otherContents']);
        if($nbOtherContents != 0) {
            throw new SyntaxException(
                'Template->getTNode1Content() : nombre de propriétés incorrect dans ce noeud : ' . $TNodeStructure['TNode'].  ' !');
        } else {
            unset($TNodeStructure['otherContents']);
            $TNodeParent = new TNode($TNodeStructure);
            return $TNodeParent;
        }
    }

    public function getTNode2Contents($TNodeStructure) {
        $nbOtherContents = count($TNodeStructure['otherContents']);
        if($nbOtherContents != 1) {
            throw new SyntaxException(
                'Template->getTNode2Contents() : nombre de propriétés incorrect dans ce noeud : ' . $TNodeStructure['TNode'].  ' !');
        } else {
            $TNodeStructure['name'] = $TNodeStructure['otherContents'][0];
            unset($TNodeStructure['otherContents']);
            $TNodeParent = new TNode($TNodeStructure);
            return $TNodeParent;
        }
    }

    public function getTNodeRoute($TNodeStructure) {
        $nbOtherContents = count($TNodeStructure['otherContents']);
        if($nbOtherContents != 1) {
            throw new SyntaxException(
                'Template->getTNodeRoute() : nombre de propriétés incorrect dans ce noeud : ' . $TNodeStructure['TNode'].  ' !');
        } else {
            $routeContents = preg_split('/:/', $TNodeStructure['otherContents'][0], -1, PREG_SPLIT_NO_EMPTY);
            $routeAlias = $routeContents[0];
            $routeParameters = array_slice($routeContents, 1);
            $TNodeStructure['route'] = \simplifying\routes\Router::getInstance()->getRoute($routeAlias, $routeParameters);
            unset($TNodeStructure['otherContents']);
            $TNodeParent = new TNode($TNodeStructure);
            return $TNodeParent;
        }
    }



    private function parse() {
        //Chargement de la hiérarchie de templates.
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
        $loopsSequence = 0;
        $endsLoopSequence = 0;
        $conditionsSequence = 0;
        $endsConditionSequence = 0;
        $blocksSequence = 0;
        $endsBlockSequence = 0;

        $TNodes = [];

        $nextTNode = $this->nextTNode($content);

        while($nextTNode != null) {
            $pos = strpos($content, $nextTNode->TNode);
            $beforeContent = substr(0, $pos - 1);
            $beforeTNodeIgnored = new TNode(['label' => TNodeLabel::IGNORED, 'content' => $beforeContent]);
            $content = substr_replace($content, "", 0, $pos - 1 + strlen($nextTNode->TNode));

            switch($nextTNode->label) {
                case TNodeLabel::CONDITION :
                    $nextTNode->addProperty('id', $conditionsSequence);
                    $conditionsSequence++;
                    $endsConditionSequence++;
                    break;
                case TNodeLabel::LOOP :
                    $nextTNode->addProperty('id', $loopsSequence);
                    $loopsSequence++;
                    $endsLoopSequence++;
                    break;
                case TNodeLabel::BLOCK :
                    $nextTNode->addProperty('id', $blocksSequence);
                    $blocksSequence++;
                    $endsBlockSequence++;
                    break;
                case TNodeLabel::END_CONDITION :
                    $nextTNode->addProperty('id', $endsConditionSequence);
                    $endsConditionSequence--;
                    break;
                case TNodeLabel::END_LOOP :
                    $nextTNode->addProperty('id', $endsLoopSequence);
                    $endsLoopSequence--;
                    break;
                case TNodeLabel::END_BLOCK :
                    $nextTNode->addProperty('id', $endsBlockSequence);
                    $endsBlockSequence--;
                    break;
            }

            $TNodes[] = $beforeTNodeIgnored;
            $TNodes[] = $nextTNode;

            $nextTNode = $this->nextTNode($content);
        }

        if($content != "") {
            $TNodeIgnored = new TNode(['label' => TNodeLabel::IGNORED, 'content' => $content]);
            $TNodes[] = $TNodeIgnored;
        }

        $rootTNode = new TNode();
        $nbTNodes = count($TNodes);
        $parentTNode = $rootTNode;
        for($i = 0; $i < $nbTNodes; $i++) {
            $TNode = $TNodes[$i];
            switch ($TNode) {
                case TNodeLabel::BLOCK :
                case TNodeLabel::END_BLOCK :
                case TNodeLabel::CONDITION :
                case TNodeLabel::END_CONDITION :
                case TNodeLabel::LOOP :
                case TNodeLabel::END_LOOP :
                default :
                    $parentTNode->addChild($TNode);
            }
        }

        return $rootTNode;
    }

    private function mergeTrees($parentTree, $childTree) {
        $mergeTree = new TNode();

        return $mergeTree;
    }

    private function parseInHtml() {

    }
}