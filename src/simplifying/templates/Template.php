<?php

namespace simplifying\templates;

use simplifying\routes\Router;

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
    private $router;

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
        $this->router = Router::getInstance();
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
                    case TNodeLabel::IF :
                        $nextTNode = $this->getTNodeIf($TNodeStructure);
                        break;
                    case TNodeLabel::ELSE :
                        $nextTNode = $this->getTNode1Content($TNodeStructure);
                        break;
                    case TNodeLabel::END_IF :
                        $nextTNode = $this->getTNode1Content($TNodeStructure);
                        break;
                    case TNodeLabel::FOR :
                        $nextTNode = $this->getTNodeFor($TNodeStructure);
                        break;
                    case TNodeLabel::END_FOR :
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
            $TNodeStructure['route'] = $this->router->getRoute($routeAlias, $routeParameters);
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
    public function getTNodeFor(array $TNodeStructure) : TNode {
        $nbOtherContents = count($TNodeStructure['otherContents']);
        if($nbOtherContents != 3) {
            throw new TemplateSyntaxException(
                'Template->getTNodeFor() : nombre de propriétés incorrect dans ce noeud : ' . $TNodeStructure['TNode'].  ' !');
        } else {
            $TNodeStructure['set'] = $TNodeStructure['otherContents'][0];
            if($TNodeStructure['otherContents'][1] != ':') {
                throw new TemplateSyntaxException(
                    'Template->getTNodeFor() : syntaxe incorrecte dans ce noeud : ' . $TNodeStructure['TNode'].  ' !');
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
    public function getTNodeIf(array $TNodeStructure) : TNode {
        $nbOtherContents = count($TNodeStructure['otherContents']);
        if($nbOtherContents != 1) {
            throw new TemplateSyntaxException(
                'Template->getTNodeIf() : nombre de propriétés incorrect dans ce noeud : ' . $TNodeStructure['TNode'].  ' !');
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
     * @throws UnfindableTemplateVariableException
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
                case TNodeLabel::IF :
                case TNodeLabel::FOR :
                    $nextTNode->id = $sequence;
                    $id = $sequence;
                    $sequence++;
                    break;
                case TNodeLabel::ELSE :
                    $nextTNode->id = $id;
                    break;
                case TNodeLabel::END_IF :
                case TNodeLabel::END_FOR :
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
                             Noeud concerné : " . $TNode->TNode . " !");
                    }
                    break;
                case TNodeLabel::FOR :
                case TNodeLabel::BLOCK :
                    $parentTNode->addChild($TNode);
                    $previousParentsTNode[] = $parentTNode;
                    $parentTNode = $TNode;
                    break;
                case TNodeLabel::IF :
                    $TNodeThen = new TNode(['label' => TNodeLabel::THEN, 'id' => $TNode->id]);
                    $parentTNode->addChild($TNode);
                    $TNode->addChild($TNodeThen);
                    $previousParentsTNode[] = $parentTNode;
                    $previousParentsTNode[] = $TNode;
                    $parentTNode = $TNodeThen;
                    break;
                case TNodeLabel::ELSE :
                    if(!$parentTNode->is(TNodeLabel::THEN)) {
                        throw new TemplateSyntaxException(
                            "Template->parseInTree() : désordre dans les noeuds de condition, noeud concerné : " . $TNode->TNode . " !");
                    }
                    $TNodeIf = $previousParentsTNode[count($previousParentsTNode) - 1];
                    $TNodeIf->addChild($TNode);
                    $parentTNode = $TNode;
                    break;
                case TNodeLabel::END_BLOCK :
                case TNodeLabel::END_FOR :
                case TNodeLabel::END_IF :
                    if(!$parentTNode->isComplementaryWith($TNode)) {
                        throw new TemplateSyntaxException(
                            "Template->parseInTree() : désordre dans les noeuds de template, noeud ouvrant : " .
                             $parentTNode->TNode .", noeud fermant : " . $TNode->TNode . " !");
                    }
                    $parentTNode = array_pop($previousParentsTNode);
                    if($parentTNode->is(TNodeLabel::IF)) {
                        $parentTNode = array_pop($previousParentsTNode);
                    }
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
     * @throws UnfindableTemplateVariableException
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
            case TNodeLabel::IF :
                $parsingContent .= $this->parseTNodeIf($TNode);
                break;
            case TNodeLabel::FOR :
                $parsingContent .= $this->parseTNodeFor($TNode);
                break;
            case TNodeLabel::IGNORED :
                $parsingContent .= $this->parseTNodeIgnored($TNode);
                break;
            case TNodeLabel::ROOT :
            case TNodeLabel::BLOCK :
                $parsingContent .= $this->parseChildrenTNode($TNode);
            //case TNodeLabel::PARENT :
            //case TNodeLabel::ABSTRACT_BLOCK :
            //case TNodeLabel::END_FOR :
            //case TNodeLabel::END_IF :
            //case TNodeLabel::END_BLOCK :
            //case TNodeLabel::ELSE :
        }

        return $parsingContent;
    }

    /**
     * @param TNode $TNode
     * @return string
     * @throws UnfindableTemplateVariableException
     */
    private function parseTNodeVal(TNode $TNode) : string {
        return $this->parseTVar($TNode->name);
    }

    /**
     * @param TNode $TNodeRoute
     * @return string
     */
    private function parseTNodeRoute(TNode $TNodeRoute) : string {
        return $TNodeRoute->route;
    }

    /**
     * @param TNode $TNodeIgnored
     * @return string
     */
    private function parseTNodeIgnored(TNode $TNodeIgnored) : string {
        return $TNodeIgnored->TNode;
    }

    /**
     * @param TNode $TNodeIf
     * @return string
     * @throws UnfindableTemplateVariableException
     * @throws TemplateSyntaxException
     */
    private function parseTNodeIf(TNode $TNodeIf) : string {
        //Récupération de la valeur de la condition.
        $condition = $this->parseTVar($TNodeIf->condition);
        //Implémentation de la condition.
        if($condition) {
            $childTNodeThen = $TNodeIf->searchChildTNodes(function($child){return $child->is(TNodeLabel::THEN);})[0];
            $parsingContent = $this->parseChildrenTNode($childTNodeThen);
        } else {
            $childTNodeElse = $TNodeIf->searchChildTNodes(function($child){return $child->is(TNodeLabel::ELSE);})[0];
            $parsingContent = $this->parseChildrenTNode($childTNodeElse);

        }
        return $parsingContent;
    }

    /**
     * @param TNode $TNodeFor
     * @return string
     * @throws TemplateSyntaxException
     * @throws UnfindableTemplateVariableException
     */
    private function parseTNodeFor(TNode $TNodeFor) : string {
        //Récupération de la valeur du set du for.
        $set = $this->parseTVar($TNodeFor->set);
        $element = $TNodeFor->element;
        //Implémentation du for.
        $parsingContent = "";
        foreach($set as $key => $value) {
            $this->vars[$element] = $value;
            $parsingContent .=  $this->parseChildrenTNode($TNodeFor);
        }
        //Destruction de la variable du for.
        unset($this->vars[$element]);

        return $parsingContent;
    }

    /**
     * @param TNode $TNode
     * @return string
     * @throws TemplateSyntaxException
     * @throws UnfindableTemplateVariableException
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
     * @param string $nameTVar
     * @return array|mixed
     * @throws UnfindableTemplateVariableException
     */
    private function parseTVar(string $nameTVar) {
        switch($nameTVar) {
            case TVarLabel::INTERNAL_VALUES :
                $TVar = $this->internalValues;
                break;
            case TVarLabel::EXTERNAL_PARAMETERS :
                $TVar = $this->externalParameters;
                break;
            case TVarLabel::GET :
                $TVar = $_GET;
                break;
            case TVarLabel::POST :
                $TVar = $_POST;
                break;
            case TVarLabel::SESSION :
                $TVar = $_SESSION;
                break;
            default:
                if(isset($this->vars[$nameTVar])) {
                    $TVar = $this->vars[$nameTVar];
                } else {
                    if(preg_match('/\.{1}/', $nameTVar)) {
                        $partsTVar = preg_split("/\.{1}/", $nameTVar, -1, PREG_SPLIT_NO_EMPTY);
                        $set = array_shift($partsTVar);
                        switch($set) {
                            case TVarLabel::CURRENT_ROUTE :
                                $TVar = $this->parseLongTVar($nameTVar, $this->router->currentRoute, $partsTVar);
                                break;
                            case TVarLabel::INTERNAL_VALUES :
                                $TVar = $this->parseLongTVar($nameTVar, $this->internalValues, $partsTVar);
                                break;
                            case TVarLabel::EXTERNAL_PARAMETERS :
                                $TVar = $this->parseLongTVar($nameTVar, $this->externalParameters, $partsTVar);
                                break;
                            case TVarLabel::GET :
                                $TVar = $this->parseLongTVar($nameTVar, $_GET, $partsTVar);
                                break;
                            case TVarLabel::POST :
                                $TVar = $this->parseLongTVar($nameTVar, $_POST, $partsTVar);
                                break;
                            case TVarLabel::SESSION :
                                $TVar = $this->parseLongTVar($nameTVar, $_SESSION, $partsTVar);
                                break;
                            default:
                                array_unshift($partsTVar, $set);
                                $TVar = $this->parseLongTVar($nameTVar, $this->vars, $partsTVar);
                                break;
                        }
                    } else {
                        throw new UnfindableTemplateVariableException(
                            "Template->parseTVar() : la variable $nameTVar est introuvable !");
                    }
                }
        }
        return $TVar;
    }

    /**
     * @param string $nameTVar
     * @param $set
     * @param array $partsTVar
     * @return array|mixed
     * @throws UnfindableTemplateVariableException
     */
    private function parseLongTVar(string $nameTVar, $set, array $partsTVar) {
        $nbPartsVal = count($partsTVar);
        if($nbPartsVal == 0) {
            throw new UnfindableTemplateVariableException(
                "Template->parseLongTVar() : la variable $nameTVar est introuvable !");
        }

        $TVar = $set;
        foreach($partsTVar as $key => $partTVar) {
            if(is_array($TVar) && array_key_exists($partTVar, $TVar) !== false) {
                $TVar = $TVar[$partTVar];
            } else {
                if(is_object($TVar) && ($TVar->$partTVar !== false || isset($TVar->$partTVar))) {
                    $TVar = $TVar->$partTVar;
                } else {
                    if($TVar == $this->vars) {
                        throw new UnfindableTemplateVariableException(
                            "Template->parseLongTVar() : la variable $nameTVar est introuvable !");
                    } else {
                        throw new UnfindableTemplateVariableException(
                            "Template->parseLongTVar() : $partTVar est introuvable dans $nameTVar !");
                    }
                }
            }
        }

        return $TVar;
    }
}