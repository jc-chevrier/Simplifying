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
    const regExpTNode = "<{2} *\/{0,1}[a-zA-Z]+ *[a-zA-Z0-9-_ \.:]+ *>{2}";

    private static $rootRelativePath = ".\\..\\..\\app\\views\\";
    private static $rootAbsolutePath;

    private $name;

    private $externalParameters;
    private $internalValues;

    private $vars;



    /**
     * Template constructor.
     * @param string $name
     * @param array $externalParameters
     */
    public function __construct(string $name, array $externalParameters = [])
    {
        $this->name = $name;

        $this->externalParameters = $externalParameters;
        $this->internalValues = [];
        $this->vars = [];

        Template::initialiseRootAbsolutePath();
    }


    /**
     *
     */
    private static function initialiseRootAbsolutePath() {
        if(Template::$rootAbsolutePath == null) {
            if(Template::isRelativePath(Template::$rootRelativePath)) {
                Template::$rootAbsolutePath = Template::parseInAbsolutePath(Template::$rootRelativePath);
            } else {
                Template::$rootAbsolutePath = Template::$rootRelativePath;
            }
        }
    }

    /**
     * @param string $path
     * @return bool
     */
    private static function isRelativePath(string $path) : bool {
        $dirs = explode('\\', $path);
        return array_search('.', $dirs) !== false || array_search('..', $dirs) !== false;
    }

    /**
     * @param string $relativePath
     * @return string
     */
    private static function parseInAbsolutePath(string $relativePath) : string {
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


    /**
     * @param string $path
     * @param array $params
     * @throws TemplateSyntaxException
     */
    public static function render($path, $params = []) : void {
        $template = new Template($path, $params);
        $template->_render();
    }

    /**
     * @throws TemplateSyntaxException
     */
    public function _render() : void {
        $parsedContent = $this->parse();
        View::render($parsedContent);
    }


    /**
     * @param string $TName
     * @return string
     */
    private function getTAbsolutePath(string $TName) : string {
        return Template::$rootAbsolutePath . $TName . '.html';
    }

    /**
     * @param string $path
     * @return bool|string
     */
    private function getTContent(string $path) : string {
        $TContent = file_get_contents($path);
        if($TContent == false) {
            throw new \InvalidArgumentException('Le chargement du template a échoué !');
        }
        return $TContent;
    }


    /**
     * @return array
     * @throws TemplateSyntaxException
     */
    private function getTHierarchy() : array {
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


    /**
     * @param string $content
     * @return bool|mixed
     */
    private function nextTNodeContents(string $content) {
        $matches = [];
        $matchesFound = preg_match('/' . Template::regExpTNode . '/', $content, $matches);
        if($matchesFound) {
            return $matches[0];
        } else {
            return false;
        }
    }

    /**
     * @param string $content
     * @return bool|mixed|TNode
     * @throws TemplateSyntaxException
     */
    private function nextTNode(string $content) {
        //<<contents>> -> contents.
        $nextTNode = $this->nextTNodeContents($content);
        if($nextTNode == false) {
            return false;
        } else {
            //Récupération du noeud.
            $contentsStr = $this->getSimpleTNodeContents($nextTNode);
            //Split sur les espaces.
            $contentsArray = preg_split("/ +/", $contentsStr, -1, PREG_SPLIT_NO_EMPTY);

            if(count($contentsArray) == 0) {
                throw new TemplateSyntaxException("Template->nextTNode() : noeud de template vide : $nextTNode !");
            } else {
                //Récupération de la structure du noeud.
                $TNodeStructure = [ 'TNode' => $nextTNode ];
                $aContent = strtolower(array_shift($contentsArray));
                if(TNodeLabel::isTNodeLabel($aContent)) {
                    $TNodeStructure['label'] = $aContent;
                } else {
                    throw new TemplateSyntaxException("Template->nextTNode() : noeud de template incorrect : $nextTNode !");
                }
                $TNodeStructure['otherContents'] = $contentsArray;

                //Structure du noeud -> Noeud de template.
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
                        $nextTNode = $this->getTNodeCondition($TNodeStructure);
                        break;
                    case TNodeLabel::CONDITION_ELSE :
                        $nextTNode = $this->getTNode1Content($TNodeStructure);
                        break;
                    case TNodeLabel::END_CONDITION :
                        $nextTNode = $this->getTNode1Content($TNodeStructure);
                        break;
                    case TNodeLabel::LOOP :
                        $nextTNode = $this->getTNodeLoop($TNodeStructure);
                        break;
                    case TNodeLabel::END_LOOP :
                        $nextTNode = $this->getTNode1Content($TNodeStructure);
                        break;
                }

                return $nextTNode;
            }
        }
    }

    /**
     * @param string $TNode
     * @return bool|string
     */
    private function getSimpleTNodeContents(string $TNode) : string {
        return substr($TNode, 2, -2);
    }



    /**
     * @param  array $TNodeStructure
     * @return TNode
     * @throws TemplateSyntaxException
     */
    public function getTNode1Content(array $TNodeStructure) : TNode {
        $nbOtherContents = count($TNodeStructure['otherContents']);
        if($nbOtherContents != 0) {
            throw new TemplateSyntaxException(
                'Template->getTNode1Content() : nombre de propriétés incorrect dans ce noeud : ' . $TNodeStructure['TNode'].  ' !');
        } else {
            unset($TNodeStructure['otherContents']);
            $TNode = new TNode($TNodeStructure);
            return $TNode;
        }
    }

    /**
     * @param array $TNodeStructure
     * @return TNode
     * @throws TemplateSyntaxException
     */
    public function getTNode2Contents(array $TNodeStructure) : TNode {
        $nbOtherContents = count($TNodeStructure['otherContents']);
        if($nbOtherContents != 1) {
            throw new TemplateSyntaxException(
                'Template->getTNode2Contents() : nombre de propriétés incorrect dans ce noeud : ' . $TNodeStructure['TNode'].  ' !');
        } else {
            $TNodeStructure['name'] = $TNodeStructure['otherContents'][0];
            unset($TNodeStructure['otherContents']);
            $TNode = new TNode($TNodeStructure);
            return $TNode;
        }
    }

    /**
     * @param array $TNodeStructure
     * @return TNode
     * @throws TemplateSyntaxException
     */
    public function getTNodeRoute(array $TNodeStructure) : TNode {
        $nbOtherContents = count($TNodeStructure['otherContents']);
        if($nbOtherContents != 1) {
            throw new TemplateSyntaxException(
                'Template->getTNodeRoute() : nombre de propriétés incorrect dans ce noeud : ' . $TNodeStructure['TNode'].  ' !');
        } else {
            $routeContents = preg_split('/:/', $TNodeStructure['otherContents'][0], -1, PREG_SPLIT_NO_EMPTY);
            $routeAlias = $routeContents[0];
            $routeParameters = array_slice($routeContents, 1);
            $TNodeStructure['route'] = \simplifying\routes\Router::getInstance()->getRoute($routeAlias, $routeParameters);
            unset($TNodeStructure['otherContents']);
            $TNode = new TNode($TNodeStructure);
            return $TNode;
        }
    }

    /**
     * @param array $TNodeStructure
     * @return TNode
     * @throws TemplateSyntaxException
     */
    public function getTNodeLoop(array $TNodeStructure) : TNode {
        $nbOtherContents = count($TNodeStructure['otherContents']);
        if($nbOtherContents != 3) {
            throw new TemplateSyntaxException(
                'Template->getTNodeLoop() : nombre de propriétés incorrect dans ce noeud : ' . $TNodeStructure['TNode'].  ' !');
        } else {
            $TNodeStructure['set'] = $TNodeStructure['otherContents'][0];
            if($TNodeStructure['otherContents'][1] != ':') {
                throw new TemplateSyntaxException(
                    'Template->getTNodeLoop() : syntaxe incorrecte dans ce noeud : ' . $TNodeStructure['TNode'].  ' !');
            }
            $TNodeStructure['element'] = $TNodeStructure['otherContents'][2];
            unset($TNodeStructure['otherContents']);
            $TNode = new TNode($TNodeStructure);
            return $TNode;
        }
    }

    /**
     * @param array $TNodeStructure
     * @return TNode
     * @throws TemplateSyntaxException
     */
    public function getTNodeCondition(array $TNodeStructure) : TNode {
        $nbOtherContents = count($TNodeStructure['otherContents']);
        if($nbOtherContents != 1) {
            throw new TemplateSyntaxException(
                'Template->getTNodeCondition() : nombre de propriétés incorrect dans ce noeud : ' . $TNodeStructure['TNode'].  ' !');
        } else {
            $TNodeStructure['condition'] = $TNodeStructure['otherContents'][0];
            unset($TNodeStructure['otherContents']);
            $TNode = new TNode($TNodeStructure);
            return $TNode;
        }
    }



    /**
     * @return string
     * @throws TemplateSyntaxException
     */
    private function parse() : string {
        //Chargement de la hiérarchie de templates.
        $hierarchy = $this->getTHierarchy();
        $TContents = $hierarchy['TContents'];
        //Parsing template -> arbre.
        $firstTContent = array_shift($TContents);
        //Parsing en arbre n-aire du template this.
        $tree = $this->parseInTree($firstTContent);
        //Pour vérifier le contenu des arbres :
        //echo $tree->toString(function($keyProperty) {if($keyProperty == 'TNode') {return false; } return true; });
        //echo $tree;
        foreach($TContents as $key => $TChildContent) {
            //Parsing des templates parents si existant.
            $childTree = $this->parseInTree($TChildContent);
            //Fusion des arbres.
            $tree = $this->mergeTrees($tree, $childTree);
        }
        echo $tree->toString(function($keyProperty) {if($keyProperty == 'TNode') {return false; } return true; });
        //Parsing arbre -> contenu.
        $parsedTContent = $this->parseInContent($tree);
        return $parsedTContent;
    }

    /**
     * @param string $content
     * @return TNode
     * @throws TemplateSyntaxException
     */
    private function parseInTree(string $content) : TNode {
        //Parsing du template en tableau de noeuds de template.
        $sequence = 0;
        $id = 0;
        $TNodes = [];
        $nextTNode = $this->nextTNode($content);
        while($nextTNode != false) {
            switch($nextTNode->label) {
                case TNodeLabel::CONDITION :
                case TNodeLabel::LOOP :
                    $nextTNode->id = $sequence;
                    $id = $sequence;
                    $sequence++;
                    break;
                case TNodeLabel::CONDITION_ELSE :
                    $nextTNode->id = $id;
                    break;
                case TNodeLabel::END_CONDITION :
                case TNodeLabel::END_LOOP :
                    $nextTNode->id = $id;
                    $id--;
                    break;
            }

            $pos = strpos($content, $nextTNode->TNode);
            $beforeContent = substr($content, 0, $pos);
            if($beforeContent != '') {
                $beforeTNodeIgnored = new TNode(['label' => TNodeLabel::IGNORED, 'TNode' => $beforeContent]);
                $TNodes[] = $beforeTNodeIgnored;
            }

            $TNodes[] = $nextTNode;

            $content = substr_replace($content, "", 0, $pos + strlen($nextTNode->TNode));
            $nextTNode = $this->nextTNode($content);
        }

        if($content != "") {
            $TNodeIgnored = new TNode(['label' => TNodeLabel::IGNORED, 'TNode' => $content]);
            $TNodes[] = $TNodeIgnored;
        }

        //Création de l'arbre à partir du tableau de noeuds de template.
        $rootTNode = TNode::getATNodeRoot();
        $parentTNode = $rootTNode;
        $previousParentsTNode = [];
        foreach($TNodes as $key => $TNode) {
            switch ($TNode->label) {
                case TNodeLabel::PARENT :
                    if(!($parentTNode->is(TNodeLabel::ROOT) && !$parentTNode->hasChildren())) {
                        throw new TemplateSyntaxException(
                            "Template->parseInTree() : un noeud <<parent ...> doit toujours être déclaré en premier noeud d'un template !
                             Voici le neoud à l'origine de l'erreur : " . $TNode->TNode . " !");
                    }
                    break;
                case TNodeLabel::LOOP :
                case TNodeLabel::BLOCK :
                case TNodeLabel::CONDITION :
                    $previousParentsTNode[] = $parentTNode;
                    $parentTNode->addChild($TNode);
                    $parentTNode = $TNode;
                    break;
                case TNodeLabel::CONDITION_ELSE :
                    $previousParentsTNode[count($previousParentsTNode) - 1]->addChild($TNode);
                    $parentTNode = $TNode;
                    break;
                case TNodeLabel::END_BLOCK :
                case TNodeLabel::END_LOOP :
                case TNodeLabel::END_CONDITION :
                    if(!$parentTNode->isComplementaryWith($TNode) || !$parentTNode->hasSameIdThat($TNode) ) {
                        throw new TemplateSyntaxException(
                            "Template->parseInTree() : désordre dans les noeuds de template de fin, noeud ouvrant : " .
                            $parentTNode->TNode .", noeud fermant : " . $TNode->TNode . " !");
                    }
                    $parentTNode = array_pop($previousParentsTNode);
                    $parentTNode->addChild($TNode);
                    break;
                default :
                    $parentTNode->addChild($TNode);
            }
        }

        return $rootTNode;
    }

    /**
     * @param TNode $parentTree
     * @param TNode $childTree
     * @return TNode
     */
    private function mergeTrees(TNode $parentTree, TNode $childTree) : TNode {
        //Cloneage des deux arbres.
        $parentTreeClone = $parentTree->clone();
        $childTreeClone = $childTree->clone();
        //Recherche des neouds de type bloc abstrait et non abstrait.
        $abstractBlocksInParentTree = $parentTreeClone->searchTNodes(function($child){return $child->label == TNodeLabel::ABSTRACT_BLOCK;});
        $blocksInChildTree = $childTreeClone->searchTNodes(function($child){return $child->label == TNodeLabel::BLOCK;});
        //Remplacement des neouds de type bloc asbtrait par les noeuds de type bloc non abstrait correspondant.
        foreach($blocksInChildTree as $key => $block) {
            foreach($abstractBlocksInParentTree as $key2 => $abstractBlock) {
                if($abstractBlock->name == $block->name) {
                    $parent = $abstractBlock->parent;
                    $parent->replaceChild($abstractBlock, $block);
                    break;
                }
            }
        }
        return $parentTreeClone;
    }

    /**
     * @param TNode $TNode
     * @return string
     * @throws TemplateSyntaxException
     */
    private function parseInContent(TNode $TNode) : string {
        $parsingContent = "";

        //Parsing du noeud de template en contenu.
        switch ($TNode->label) {
            case TNodeLabel::VALUE :
                $parsingContent .= $this->parseTNodeVal($TNode);
                break;
            case TNodeLabel::ROUTE :
                $parsingContent .= $this->parseTNodeRoute($TNode);
                break;
            case TNodeLabel::CONDITION :
                $parsingContent .= $this->parseTNodeCondition($TNode);
                break;
            case TNodeLabel::LOOP :
                $parsingContent .= $this->parseTNodeLoop($TNode);
                break;
            case TNodeLabel::IGNORED :
                $parsingContent .= $TNode->TNode;
                break;
            case TNodeLabel::ROOT :
            case TNodeLabel::BLOCK :
                $parsingContent .= $this->parseChildrenTNode($TNode);
            //case TNodeLabel::PARENT :
            //case TNodeLabel::ABSTRACT_BLOCK :
            //case TNodeLabel::END_LOOP :
            //case TNodeLabel::END_CONDITION :
            //case TNodeLabel::END_BLOCK :
            //case TNodeLabel::CONDITION_ELSE :
        }

        return $parsingContent;
    }

    /**
     * @param TNode $TNode
     * @return string
     */
    private function parseTNodeVal(TNode $TNode) : string {
        return "TODO Val";
    }

    /**
     * @param TNode $TNodeRoute
     * @return string
     */
    private function parseTNodeRoute(TNode $TNodeRoute) : string {
        return $TNodeRoute->route . $this->parseChildrenTNode($TNodeRoute);
    }

    /**
     * @param TNode $TNodeCondition
     * @return string
     */
    private function parseTNodeCondition(TNode $TNodeCondition) : string {
        return "TODO Condition";
    }

    /**
     * @param TNode $TNodeLoop
     * @return string
     * @throws TemplateSyntaxException
     */
    private function parseTNodeLoop(TNode $TNodeLoop) : string {
        $set = $this->getVal($TNodeLoop->set);
        $element = $TNodeLoop->element;

        $parsingContent = "";
        foreach($set as $key => $value) {
            $this->vars[$element] = $value;
            $parsingContent .=  $this->parseChildrenTNode($TNodeLoop);
        }

        return $parsingContent;
    }

    /**
     * @param TNode $TNode
     * @return string
     * @throws TemplateSyntaxException
     */
    private function parseChildrenTNode(TNode $TNode) : string {
        $parsingContent = "";
        //Parsing des noeuds de template enfants en contenu.
        foreach($TNode->children as $key => $child) {
            $parsingContent .= $this->parseInContent($child);
        }
        return $parsingContent;
    }


    /**
     * @param string $nameVal
     * @return string
     * @throws TemplateSyntaxException
     */
    private function getVal(string $nameVal) : string {
        $val = "";
        switch($nameVal) {
            case TVarLabel::INTERNAL_VALUES:
                $val = $this->internalValues;
                break;
            case TVarLabel::EXTERNAL_PARAMETERS:
                $val = $this->externalParameters;
                break;
            case TVarLabel::GET:
                $val = $_GET;
                break;
            case TVarLabel::POST:
                $val = $_POST;
                break;
            case TVarLabel::SESSION:
                $val = $_SESSION;
                break;
            default:
                if(isset($this->vars[$nameVal])) {
                    $val = 'this->vars["'. $nameVal .'"]';
                } else {
                    if(preg_match('.{1}', $nameVal)) {
                        $partsVal = preg_split("/.{1}/", $nameVal, -1, PREG_SPLIT_NO_EMPTY);
                        $set = $partsVal[0];
                        switch($set) {
                            case TVarLabel::CURRENT_ROUTE:
                                $routeParameter = $partsVal[1];
                                $val = '\simplifying\routes\Router::getInstance()->currentRoute->' . $routeParameter;
                                break;
                            case TVarLabel::INTERNAL_VALUES:
                                $key = $partsVal[1];
                                $val = $this->internalValues[$key];
                                break;
                            case TVarLabel::EXTERNAL_PARAMETERS:
                                $key = $partsVal[1];
                                $val = $this->externalParameters[$key];
                                break;
                            case TVarLabel::GET:
                                $key = $partsVal[1];
                                $val = $_GET[$key];
                                break;
                            case TVarLabel::POST:
                                $key = $partsVal[1];
                                $val = $_POST[$key];
                                break;
                            case TVarLabel::SESSION:
                                $key = $partsVal[1];
                                $val = $_SESSION[$key];
                                break;
                        }
                    } else {
                        throw new TemplateSyntaxException("Template->parseNameVal() : ensemble introuvable : $nameVal !");
                    }
                }
        }
        return $val;
    }
}