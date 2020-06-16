<?php

namespace simplifying\templates;

use simplifying\PathManager;
use simplifying\routes\Router;

/**
 * Classe Template.
 *
 * T <=> Template.
 * reg exp <=> regular expression.
 * var <=> variable.
 *
 * @author CHEVRIER Jean-Christophe
 * @package simplifying\templates
 */
class Template
{
    const EXTENSION_T_FILE = ".html";
    
    const REG_EXP_T_NODE = "<{2} *\/{0,1}[a-zA-Z]+ *[^<>]* *>{1}";

    private static $ROOT_T_RELATIVE_PATH = ".\\..\\..\\app\\views\\";
    private static $ROOT_T_ABSOLUTE_PATH;

    private $name;

    private $externalParameters;
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
        $this->router = Router::getInstance();
        $this->vars = [];
        Template::initialiseRootTAbsolutePath();
    }

    /**
     *
     */
    private static function initialiseRootTAbsolutePath() {
        if(Template::$ROOT_T_ABSOLUTE_PATH == null) {
            if(PathManager::isRelativePath(Template::$ROOT_T_RELATIVE_PATH)) {
                Template::$ROOT_T_ABSOLUTE_PATH = PathManager::parseInAbsolutePath(Template::$ROOT_T_RELATIVE_PATH, __DIR__);
            } else {
                Template::$ROOT_T_ABSOLUTE_PATH = Template::$ROOT_T_RELATIVE_PATH;
            }
        }
    }


    /**
     * @param string $name
     * @param array $externalParameters
     * @throws TemplateSyntaxException
     * @throws UnfindableTemplateVariableException
     */
    public static function render(string $name, array $externalParameters = []) : void {
        $template = new Template($name, $externalParameters);
        $template->_render();
    }

    /**
     * @throws TemplateSyntaxException
     * @throws UnfindableTemplateVariableException
     */
    public function _render() : void {
        $parsedContent = $this->parse();
        View::render($parsedContent);
    }



    /**
     * Obtenir le chemin absolu d'un template.
     *
     * @param string $Tname
     * @return string
     */
    private function getTAbsolutePath(string $Tname) : string {
        return Template::$ROOT_T_ABSOLUTE_PATH . $Tname . Template::EXTENSION_T_FILE;
    }


    /**
     * Obtenir le contenu d'un template.
     *
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
     * Obtenir une hierarchie de templates.
     *
     * @return array
     * @throws TemplateSyntaxException
     */
    private function getTHierarchy() : array {
        $Tnames = [ $this->name ];
        $TPath = $this->getTAbsolutePath($this->name);
        $TPaths = [ $TPath ];
        $TContent = $this->getTContent($TPath);
        $TContents = [ $TContent ];

        $TNodeParent = $this->getNextTNode($TContent);
        while($TNodeParent != false && $TNodeParent->label === TNodeLabel::PARENT) {
            $pathOfTParent = $this->getTAbsolutePath($TNodeParent->name);
            $contentOfTParent = $this->getTContent($pathOfTParent);

            array_unshift($Tnames, $TNodeParent->name);
            array_unshift($TPaths, $pathOfTParent);
            array_unshift($TContents, $contentOfTParent);

            $TNodeParent = $this->getNextTNode($contentOfTParent);
        }

        return [ 'Tnames' => $Tnames,
                 'TPaths' => $TPaths,
                 'TContents' => $TContents ];
    }






    //------------------------------------------------------------------------------------------------------------------
    // Obtention de noeuds de template.



    /**
     * @param string $content
     * @return bool|mixed
     */
    private function getNextTNodeContents(string $content) {
        $matches = [];
        $matchesFound = preg_match('/' . Template::REG_EXP_T_NODE . '/', $content, $matches);
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
    private function getNextTNode(string $content) {
        //<<contents>> -> contents.
        $getNextTNode = $this->getNextTNodeContents($content);
        if($getNextTNode == false) {
            return false;
        } else {
            //Récupération du noeud.
            $contentsStr = $this->getSimpleTNodeContents($getNextTNode);
            //Split sur les espaces.
            $contentsArray = preg_split("/ +/", $contentsStr, -1, PREG_SPLIT_NO_EMPTY);

            if(count($contentsArray) == 0) {
                throw new TemplateSyntaxException("Template->getNextTNode() : noeud de template vide : $getNextTNode !");
            } else {
                //Récupération de la structure du noeud.
                $TNodeStructure = [ 'TNode' => $getNextTNode ];
                $aContent = strtolower(array_shift($contentsArray));
                if(TNodeLabel::isTNodeLabel($aContent)) {
                    $TNodeStructure['label'] = $aContent;
                } else {
                    throw new TemplateSyntaxException("Template->getNextTNode() : noeud de template incorrect : $getNextTNode !");
                }
                $TNodeStructure['otherContents'] = $contentsArray;

                //Structure du noeud -> Noeud de template.
                switch ($TNodeStructure['label']) {
                    case TNodeLabel::VALUE :
                    case TNodeLabel::PARENT :
                    case TNodeLabel::ABSTRACT_BLOCK :
                    case TNodeLabel::BLOCK :
                        $getNextTNode = $this->getTNode2Contents($TNodeStructure);
                        break;
                    case TNodeLabel::ROUTE :
                        $getNextTNode = $this->getTNodeRoute($TNodeStructure);
                        break;
                    case TNodeLabel::IF :
                    case TNodeLabel::IF_NOT :
                        $getNextTNode = $this->getTNode2Contents($TNodeStructure, 'condition');
                        break;
                    case TNodeLabel::TERNARY_EXPRESSION :
                        $getNextTNode = $this->getTNodeTernary($TNodeStructure);
                        break;
                    case TNodeLabel::ELSE :
                    case TNodeLabel::END_IF :
                    case TNodeLabel::END_IF_NOT :
                    case TNodeLabel::END_BLOCK :
                    case TNodeLabel::END_FOR :
                        $getNextTNode = $this->getTNode1Content($TNodeStructure);
                        break;
                    case TNodeLabel::FOR :
                        $getNextTNode = $this->getTNodeFor($TNodeStructure);
                }

                return $getNextTNode;
            }
        }
    }

    /**
     * @param string $TNode
     * @return bool|string
     */
    private function getSimpleTNodeContents(string $TNode) : string {
        return substr($TNode, 2, -1);
    }

    /**
     * @param  array $TNodeStructure
     * @return TNode
     * @throws TemplateSyntaxException
     */
    private function getTNode1Content(array $TNodeStructure) : TNode {
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
     * @param string $keyProperty
     * @return TNode
     * @throws TemplateSyntaxException
     */
    private function getTNode2Contents(array $TNodeStructure, string $keyProperty = 'name') : TNode {
        $nbOtherContents = count($TNodeStructure['otherContents']);
        if($nbOtherContents != 1) {
            throw new TemplateSyntaxException(
                'Template->getTNode2Contents() : nombre de propriétés incorrect dans ce noeud : ' . $TNodeStructure['TNode'].  ' !');
        } else {
            $TNodeStructure[$keyProperty] = $TNodeStructure['otherContents'][0];
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
    private function getTNodeRoute(array $TNodeStructure) : TNode {
        $nbOtherContents = count($TNodeStructure['otherContents']);
        if($nbOtherContents != 1) {
            throw new TemplateSyntaxException(
                'Template->getTNodeRoute() : nombre de propriétés incorrect dans ce noeud : ' . $TNodeStructure['TNode'].  ' !');
        } else {
            $contents = $this->getSimpleTNodeContents($TNodeStructure['TNode']);
            $contents = preg_split('/ *'. TNodeLabel::ROUTE .' */', $contents, -1, PREG_SPLIT_NO_EMPTY);
            $contents = preg_split('/ *:{1} */', $contents[0], -1, PREG_SPLIT_NO_EMPTY);
            $TNodeStructure['routeAlias'] = array_shift($contents);
            $TNodeStructure['routeParameters'] = $contents;
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
    private function getTNodeFor(array $TNodeStructure) : TNode {
        $nbOtherContents = count($TNodeStructure['otherContents']);
        if($nbOtherContents == 0) {
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
    private function getTNodeTernary(array $TNodeStructure) : TNode {
        $nbOtherContents = count($TNodeStructure['otherContents']);
        if($nbOtherContents == 0) {
            throw new TemplateSyntaxException(
                'Template->getTNodeTernary() : nombre de propriétés incorrect dans ce noeud : ' . $TNodeStructure['TNode'].  ' !');
        } else {
            $contents = $this->getSimpleTNodeContents($TNodeStructure['TNode']);
            $contents = preg_split('/ *'. TNodeLabel::TERNARY_EXPRESSION .' */', $contents, -1, PREG_SPLIT_NO_EMPTY);
            $contents = preg_split('/ *\?{1} */', $contents[0], -1, PREG_SPLIT_NO_EMPTY);
            $TNodeStructure['condition'] =  $contents[0];
            $contents = preg_split('/ *:{1} */', $contents[1], -1, PREG_SPLIT_NO_EMPTY);
            $TNodeStructure['then'] =  $contents[0];
            $TNodeStructure['else'] = $contents[1];
            unset($TNodeStructure['otherContents']);
            $TNode = new TNode($TNodeStructure);
            return $TNode;
        }
    }





    //------------------------------------------------------------------------------------------------------------------
    // Parsing des templates en noeuds de tamplates syntaxiques puis en langages web (html, etc.).



    /**
     * Parser un template en arbre syntaxique puis en langages web (html, etc.).
     *
     * Processus de la méthode :
     * template <=> tree <=> langages web (html, etc.).
     *
     * Pour vérifier le contenu des arbres :
     * echo $tree->toString(function($keyProperty) {if($keyProperty == 'TNode') {return false; } return true; });
     * echo $tree;
     *
     * @return string
     * @throws TemplateSyntaxException
     * @throws UnfindableTemplateVariableException
     */
    public function parse() : string {
        //Chargement de la hiérarchie de templates.
        $hierarchy = $this->getTHierarchy();
        $TContents = $hierarchy['TContents'];
        //Parsing template -> arbre.
        $firstTContent = array_shift($TContents);
        //Parsing en arbre n-aire du template this.
        $tree = $this->parseTemplateInTree($firstTContent);
        foreach($TContents as $key => $TChildContent) {
            //Parsing des templates parents si existant.
            $childTree = $this->parseTemplateInTree($TChildContent);
            //Fusion des arbres.
            $tree = $this->mergeTrees($tree, $childTree);
        }
        //Parsing arbre -> contenu.
        $parsedTContent = $this->parseTreeInWebLanguages($tree);
        return $parsedTContent;
    }





    //------------------------------------------------------------------------------------------------------------------
    // Parsing des templates en arbres syntaxiques et merge des arbres.



    /**
     * Parser un template en arbre syntaxique.
     *
     * @param string $content
     * @return TNode
     * @throws TemplateSyntaxException
     */
    private function parseTemplateInTree(string $content) : TNode {
        //Parsing du template en tableau de noeuds de template.
        $sequence = 0;
        $TNodes = [];
        $nextTNode = $this->getNextTNode($content);
        while($nextTNode != false) {
            if($nextTNode->is(TNodeLabel::FOR)) {
                $nextTNode->id = $sequence;
                $sequence++;
            }

            $pos = strpos($content, $nextTNode->TNode);
            $beforeContent = substr($content, 0, $pos);
            if($beforeContent != '') {
                $beforeTNodeIgnored = new TNode(['label' => TNodeLabel::IGNORED, 'TNode' => $beforeContent]);
                $TNodes[] = $beforeTNodeIgnored;
            }

            $TNodes[] = $nextTNode;

            $content = substr_replace($content, "", 0, $pos + strlen($nextTNode->TNode));
            $nextTNode = $this->getNextTNode($content);
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
                            "Template->parseTemplateInTree() : un noeud <<parent ...> doit toujours être déclaré en premier 
                             noeud d'un template ! Noeud concerné : " . $TNode->TNode . " !");
                    }
                    break;
                case TNodeLabel::FOR :
                case TNodeLabel::BLOCK :
                case TNodeLabel::IF_NOT :
                    $parentTNode->addChild($TNode);
                    $previousParentsTNode[] = $parentTNode;
                    $parentTNode = $TNode;
                    break;
                case TNodeLabel::IF :
                    $TNodeThen = new TNode(['label' => TNodeLabel::THEN]);
                    $parentTNode->addChild($TNode);
                    $TNode->addChild($TNodeThen);
                    $previousParentsTNode[] = $parentTNode;
                    $previousParentsTNode[] = $TNode;
                    $parentTNode = $TNodeThen;
                    break;
                case TNodeLabel::ELSE :
                    if(!$parentTNode->is(TNodeLabel::THEN)) {
                        throw new TemplateSyntaxException(
                            "Template->parseTemplateInTree() : désordre dans les noeuds de tamplate de condition, noeud 
                             concerné : " . $TNode->TNode . " !");
                    }
                    $TNodeIf = $previousParentsTNode[count($previousParentsTNode) - 1];
                    $TNodeIf->addChild($TNode);
                    $parentTNode = $TNode;
                    break;
                case TNodeLabel::END_BLOCK :
                case TNodeLabel::END_FOR :
                case TNodeLabel::END_IF :
                case TNodeLabel::END_IF_NOT :
                    if(!$parentTNode->isComplementaryWith($TNode)) {
                        throw new TemplateSyntaxException(
                            "Template->parseTemplateInTree() : désordre dans les noeuds de template, noeud ouvrant : " .
                             $parentTNode->TNode .", noeud fermant : " . $TNode->TNode . " !");
                    }
                    $parentTNode = array_pop($previousParentsTNode);
                    if($parentTNode->is(TNodeLabel::IF)) {
                        $parentTNode = array_pop($previousParentsTNode);
                    }
                    break;
                default :
                    $parentTNode->addChild($TNode);
            }
        }
        if(!$parentTNode->is(TNodeLabel::ROOT)) {
            throw new TemplateSyntaxException("Template->parseTemplateInTree() : désordre dans les noeuds de template !");
        }

        return $rootTNode;
    }

    /**
     * Merger deux arbres syntaxiuqes issus de deux templates différents.
     *
     * @param TNode $parentTree
     * @param TNode $childTree
     * @return TNode
     */
    private function mergeTrees(TNode $parentTree, TNode $childTree) : TNode {
        //Cloneage des deux arbres.
        $parentTreeClone = $parentTree->clone();
        $childTreeClone = $childTree->clone();
        //Recherche des neouds de type bloc abstrait et non abstrait.
        $abstractBlocksInParentTree = $parentTreeClone->searchTNodes(function($child){return $child->is(TNodeLabel::ABSTRACT_BLOCK);});
        $blocksInChildTree = $childTreeClone->searchTNodes(function($child){return $child->is(TNodeLabel::BLOCK);});
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





    //------------------------------------------------------------------------------------------------------------------
    // Parsing des noeuds de tamplates syntaxiques en langages web (html, etc.).




    /**
     * @param TNode $TNode
     * @return string
     * @throws TemplateSyntaxException
     * @throws UnfindableTemplateVariableException
     */
    private function parseTreeInWebLanguages(TNode $TNode) : string {
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
            case TNodeLabel::TERNARY_EXPRESSION :
                $parsingContent .= $this->parseTNodeTernary($TNode);
                break;
            case TNodeLabel::IF_NOT :
                $parsingContent .= $this->parseTNodeIfNot($TNode);
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
     * @throws UnfindableTemplateVariableException
     */
    private function parseTNodeRoute(TNode $TNodeRoute) : string {
        $nbParameters = count($TNodeRoute->routeParameters);
        $routeParameters = (new \ArrayObject($TNodeRoute->routeParameters))->getArrayCopy();
        for($i = 0; $i < $nbParameters; $i++) {
            $routeParameter = $routeParameters[$i];
            if(strpos($routeParameter, "#") === 0) {
                $nameTVar = substr($routeParameter, 1);
                $routeParameter = $this->parseTVar($nameTVar);
            }
            $routeParameters[$i] = $routeParameter;
        }
        return $this->router->getRoute($TNodeRoute->routeAlias, $routeParameters);
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
            $searched = $TNodeIf->searchChildTNodes(function($child){return $child->is(TNodeLabel::ELSE);});
            if(count($searched) == 0) {
                $parsingContent = "";
            } else {
                $childTNodeElse = $searched[0];
                $parsingContent = $this->parseChildrenTNode($childTNodeElse);
            }
        }
        return $parsingContent;
    }

    /**
     * @param TNode $TNodeTernary
     * @return string
     * @throws UnfindableTemplateVariableException
     */
    private function parseTNodeTernary(TNode $TNodeTernary) : string {
        //Récupération de la valeur de la condition.
        $condition = $this->parseTVar($TNodeTernary->condition);
        //Implémentation de la condition.
        if($condition) {
            $parsingContent = $TNodeTernary->then;
        } else {
            if($TNodeTernary->propertyExists('else')) {
                $parsingContent = $TNodeTernary->else;
            } else {
                $parsingContent = "";
            }
        }
        return $parsingContent;
    }

    /**
     * @param TNode $TNodeIfNot
     * @return string
     * @throws TemplateSyntaxException
     * @throws UnfindableTemplateVariableException
     */
    private function parseTNodeIfNot(TNode $TNodeIfNot) : string {
        //Récupération de la valeur de la condition.
        $condition = $this->parseTVar($TNodeIfNot->condition);
        //Implémentation de la condition.
        if($condition) {
            $parsingContent = "";
        } else {
            $parsingContent = $this->parseChildrenTNode($TNodeIfNot);
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
            $parsingContent .= $this->parseTreeInWebLanguages($child);
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
                        $partsTVar = preg_split("/ *\.{1} */", $nameTVar, -1, PREG_SPLIT_NO_EMPTY);
                        $set = array_shift($partsTVar);
                        switch($set) {
                            case TVarLabel::CURRENT_ROUTE :
                                $TVar = $this->parseLongTVar($nameTVar, $this->router->currentRoute, $partsTVar);
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