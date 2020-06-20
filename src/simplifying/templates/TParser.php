<?php

namespace simplifying\templates;

use simplifying\routes\Router;

class TParser
{
    /**
     * @var TNode   Arbre repéresentant le template à parser.
     */
    private $tree;
    /**
     * @var array   Paramètres du template.
     */
    private $externalParameters;
    /**
     * @var Router  Router.
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
     * TParser constructor.
     * @param TNode $tree
     * @param array $externalParameters
     */
    public function __construct(TNode $tree, array $externalParameters) {
        $this->tree = $tree;
        $this->externalParameters = $externalParameters;
        $this->vars = [];
        $this->router = Router::getInstance();
    }


    /**
     * ¨Parser un arbre en langages web.
     *
     * @param TNode $tree
     * @param array $externalParameters
     * @return string
     * @throws TSyntaxException
     * @throws TVariableException
     */
    public static function parseTreeInWebLanguages(TNode $tree, array $externalParameters) : string {
        $TParser = new TParser($tree, $externalParameters);
        $parsingContent = $TParser->_parseTreeInWebLanguages();
        return $parsingContent;
    }

    /**
     * @return string
     * @throws TSyntaxException
     * @throws TVariableException
     */
    public function _parseTreeInWebLanguages() : string {
        $parsingContent = $this->parseTNode($this->tree);
        return $parsingContent;
    }

    /**
     * @param TNode $TNode
     * @return string
     * @throws TSyntaxException
     * @throws TVariableException
     */
    private function parseTNode(TNode $TNode) : string {
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
            $parsingContent .= $this->parseTNode($child);
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