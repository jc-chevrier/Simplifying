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
    /**
     * Extension des noeuds de template.
     */
    const EXTENSION_T = ".html";
    /**
     * @var string       Chemin relatif du répertoire racine des templates.
     */
    private static $ROOT_T_RELATIVE_PATH = ".\\..\\..\\app\\views\\";
    /**
     * @var string       Chemin absolu du répertoire racine des templates.
     */
    private static $ROOT_T_ABSOLUTE_PATH;
    /**
     * @var string      Nom du template.
     */
    private $name;
    /**
     * @var array       Paramètres externes du template.
     */
    private $externalParameters;
    /**
     * @var Router
     */
    private $router;
    /**
     * @var array       Tableau des variables internes.
     */
    private $vars;
    /**
     * @var string      Id du scope for en cours de parsing.
     */
    private $currentForId;



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
     * @throws TSyntaxException
     * @throws TVariableException
     */
    public static function render(string $name, array $externalParameters = []) : void {
        $template = new Template($name, $externalParameters);
        $template->_render();
    }

    /**
     * @throws TSyntaxException
     * @throws TVariableException
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
        return Template::$ROOT_T_ABSOLUTE_PATH . $Tname . Template::EXTENSION_T;
    }





    //------------------------------------------------------------------------------------------------------------------
    // Parsing des templates en noeuds de tamplates syntaxiques puis en langages web (html, etc.).



    /**
     * Parser un template en arbre syntaxique puis en langages web (html, etc.).
     *
     * Processus de la méthode :
     * template(s) <=> arbre(s) <=> langages web (html, etc.).
     *
     * @return string
     * @throws TSyntaxException
     * @throws TVariableException
     */
    public function parse() : string {
        //Parsing du template en arbre et des templates parents en arbres.
        $trees = $this->parseInHierarchyOfTrees();
        $tree = array_shift($trees);
        foreach($trees as $key => $childTree) {
            //Merge / fusion des arbres.
            $tree = $this->mergeTrees($tree, $childTree);
        }
        //Parsing arbre -> contenu.
        $parsedTContent = $this->parseTreeInWebLanguages($tree);
        return $parsedTContent;
    }

    /**
     * @throws TSyntaxException
     */
    private function parseInHierarchyOfTrees() : array {
        $TPath = $this->getTAbsolutePath($this->name);
        $tree = $this->parseTemplateInTree($TPath);
        $trees = [];
        $trees[] = $tree;
        $firstTNode = $tree->hasChildren() ? $tree->child(0) : false;
        while($firstTNode != false && $firstTNode->is(TNodeLabel::PARENT)) {
            $TPath = $this->getTAbsolutePath($firstTNode->name);
            $tree = $this->parseTemplateInTree($TPath);
            array_unshift($trees, $tree);
            $firstTNode = $tree->hasChildren() ? $tree->child(0) : false;
        }
        return $trees;
    }

    /**
     * Parser un template en arbre syntaxique.
     *
     * echo $tree->toString(function($keyProperty) {if($keyProperty == 'TNode') {return false; } return true; });
     *
     * @param string $TPath
     * @return TNode
     * @throws TSyntaxException
     */
    private function parseTemplateInTree(string $TPath) : TNode {
        $tree = TAnalyzer::parseTemplateInTree($TPath);
        return $tree;
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
                if($block->hasSamePropertyThat("name", $abstractBlock)) {
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
     * @throws TSyntaxException
     * @throws TVariableException
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
     * @throws TVariableException
     */
    private function parseTNodeVal(TNode $TNode) : string {
        return $this->parseTVar($TNode->name);
    }

    /**
     * @param TNode $TNodeRoute
     * @return string
     * @throws TVariableException
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
     * @throws TVariableException
     * @throws TSyntaxException
     */
    private function parseTNodeIf(TNode $TNodeIf) : string {
        //Récupération de la valeur de la condition.
        $condition = $this->parseTVar($TNodeIf->condition);
        //Implémentation de la condition.
        if($condition) {
            $childTNodeThen = $TNodeIf->child(0);
            $parsingContent = $this->parseChildrenTNode($childTNodeThen);
        } else {
            if($TNodeIf->nbChildren == 1) {
                $parsingContent = "";
            } else {
                $childTNodeElse = $TNodeIf->child(1);
                $parsingContent = $this->parseChildrenTNode($childTNodeElse);
            }
        }
        return $parsingContent;
    }

    /**
     * @param TNode $TNodeTernary
     * @return string
     * @throws TVariableException
     */
    private function parseTNodeTernary(TNode $TNodeTernary) : string {
        //Récupération de la valeur de la condition.
        $condition = $this->parseTVar($TNodeTernary->condition);
        //Implémentation de la condition.
        if($condition) {
            $parsingContent = $TNodeTernary->then;
        } else {
            $parsingContent = $TNodeTernary->else;
        }
        return $parsingContent;
    }

    /**
     * @param TNode $TNodeIfNot
     * @return string
     * @throws TSyntaxException
     * @throws TVariableException
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
     * @throws TSyntaxException
     * @throws TVariableException
     */
    private function parseTNodeFor(TNode $TNodeFor) : string {
        $varExists = array_key_exists($TNodeFor->element, $this->vars);
        if($varExists) {
            throw new TVariableException(
                "Template->parseTNodeFor() : nom de variable déjà utilisé dans le scope courant ! 
                 Variable concernée : " . $TNodeFor->element ." !");
        }
        $isSequence = preg_match('/\.{2}/', $TNodeFor->set);
        if($isSequence) {
            $parsingContent = $this->parseTNodeForSequence($TNodeFor);
        } else {
            $parsingContent = $this->parseTNodeForeach($TNodeFor);
        }
        return $parsingContent;
    }

    /**
     * @param TNode $TNodeFor
     * @return string
     * @throws TSyntaxException
     * @throws TVariableException
     */
    private function parseTNodeForSequence(TNode $TNodeFor) : string {
        $element = $TNodeFor->element;
        //Implémentation du for.
        $bounds = preg_split("/\.{2}/",  $TNodeFor->set, -1, PREG_SPLIT_NO_EMPTY);
        $lowerBound = $bounds[0];
        $upperBound = $bounds[1];
        $nbElements = $upperBound - $lowerBound + 1;
        $lastIndex = $upperBound - 1;
        $parsingContent = "";
        $forId = "for".$TNodeFor->id;
        $previousForId = $this->currentForId;
        $this->vars[$forId]["scope"] = $previousForId == null ? null : $this->vars[$previousForId];
        $this->currentForId = $forId;
        $this->vars[$forId]["size"] = $nbElements;
        $this->vars[$forId]["min"] = $lowerBound;
        $this->vars[$forId]["max"] = $upperBound;
        for($i = $lowerBound; $i <= $upperBound; $i++) {
            $this->vars[$forId]["isFirstIteration"] = $i == $lowerBound;
            $this->vars[$forId]["isLastIteration"] = $i == $lastIndex;
            $this->vars[$element] = $i;
            $parsingContent .=  $this->parseChildrenTNode($TNodeFor);
        }
        //Destruction des variables du for.
        $this->currentForId = $previousForId;
        unset($this->vars[$element]);
        unset($this->vars[$forId]);
        return $parsingContent;
    }

    /**
     * @param TNode $TNodeFor
     * @return string
     * @throws TSyntaxException
     * @throws TVariableException
     */
    private function parseTNodeForeach(TNode $TNodeFor) : string {
        //Récupération de la valeur du set du for.
        $set = $this->parseTVar($TNodeFor->set);
        $element = $TNodeFor->element;
        //Implémentation du for.
        $nbElements = count($set);
        $lastIndex = $nbElements - 1;
        $parsingContent = "";
        $forId = "for" . $TNodeFor->id;
        $previousForId = $this->currentForId;
        $this->vars[$forId]["scope"] = $previousForId == null ? null : $this->vars[$previousForId];
        $this->currentForId = $forId;
        $this->vars[$forId]["size"] = $nbElements;
        for($i = 0; $i < $nbElements; $i++) {
            $this->vars[$forId]["index"] = $i + 1;
            $this->vars[$forId]["isFirstIteration"] = $i == 0;
            $this->vars[$forId]["isLastIteration"] = $i == $lastIndex;
            $this->vars[$element] = $set[$i];
            $parsingContent .=  $this->parseChildrenTNode($TNodeFor);
        }
        //Destruction des variables du for.
        $this->currentForId = $previousForId;
        unset($this->vars[$element]);
        unset($this->vars[$forId]);
        return $parsingContent;
    }

    /**
     * @param TNode $TNode
     * @return string
     * @throws TSyntaxException
     * @throws TVariableException
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
     * @throws TVariableException
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
                    $isALongTVar = preg_match('/\.{1}/', $nameTVar);
                    if($isALongTVar) {
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
                            case TNodeLabel::FOR :
                                $TVar = $this->parseLongTVar($nameTVar, $this->vars[$this->currentForId], $partsTVar);
                                break;
                            default:
                                array_unshift($partsTVar, $set);
                                $TVar = $this->parseLongTVar($nameTVar, $this->vars, $partsTVar);
                                break;
                        }
                    } else {
                        throw new TVariableException(
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
     * @throws TVariableException
     */
    private function parseLongTVar(string $nameTVar, $set, array $partsTVar) {
        $nbPartsVal = count($partsTVar);
        if($nbPartsVal == 0) {
            throw new TVariableException(
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
                        throw new TVariableException(
                            "Template->parseLongTVar() : la variable $nameTVar est introuvable !");
                    } else {
                        throw new TVariableException(
                            "Template->parseLongTVar() : $partTVar est introuvable dans $nameTVar !");
                    }
                }
            }
        }

        return $TVar;
    }
}