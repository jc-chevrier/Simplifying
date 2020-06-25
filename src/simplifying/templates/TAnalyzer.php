<?php

namespace simplifying\templates;

/**
 * Class TAnalyzer.
 *
 * Cette classe est un analyseur de la structure syntaxique
 * des templates. Cet analyseur analyse le code d'un template
 * et produit à partir son analyse l'arbre syntaxique correspondant
 * au template en question.
 *
 * T <=> Template
 *
 * @author CHEVRIER Jean-Christophe
 * @package simplifying\templates
 */
class TAnalyzer
{
    /**
     * @var TScanner        Scanner sur le template.
     */
    private $TScanner;



    /**
     * TAnalyzer constructor.
     * @param string $TPath
     */
    public function __construct(string $TPath) {
        $this->TScanner = new TScanner($TPath);
    }


    /**
     * Parser un template en arbre.
     *
     * @param string $TPath
     * @return TNode
     * @throws TSyntaxException
     */
    public static function parseTemplateInTree(string $TPath) : TNode {
        $TAnalyzer = new TAnalyzer($TPath);
        $tree = $TAnalyzer->_parseTemplateInTree();
        return $tree;
    }
    /**
     * Parser un template en arbre.
     *
     * @return TNode
     * @throws TSyntaxException
     */
    public function _parseTemplateInTree() : TNode {
        //Parsing du template en tableau de noeuds de template.
        $TNodes = $this->parseTemplateInArrayOfTNodes();
        //Création de l'arbre à partir du tableau de noeuds de template.
        $rootTNode = $this->orderTNodes($TNodes);
        return $rootTNode;
    }



    /**
     * Parser un template en tableau de noeuds de template.
     *
     * @return array
     * @throws TSyntaxException
     */
    private function parseTemplateInArrayOfTNodes() : array {
        $TNodes = [];
        $this->TScanner->forEach(function($nextTNodeArray, $key, &$TNodes) {
            $nextTNode = $this->analyzeTNode($nextTNodeArray);
            if($nextTNode->is(TNodeLabel::FOR)) {
                $nextTNode->id = $key;
            }
            $TNodes[] = $nextTNode;
        }, $TNodes);
        return $TNodes;
    }

    /**
     * Ordonner en arbre les noeuds d'un tableau de noeuds de template.
     *
     * @param array $TNodes
     * @return TNode
     * @throws TSyntaxException
     */
    private function orderTNodes(array $TNodes) : TNode {
        $rootTNode = TNode::getATNodeRoot();
        $parentTNode = $rootTNode;
        $previousParentsTNode = [];

        foreach($TNodes as $key => $TNode) {
            switch ($TNode->label) {
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
                        throw new TSyntaxException(
                            "TAnalyzer->orderTNodes() : désordre dans les noeuds de tamplate de condition, noeud 
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
                        throw new TSyntaxException(
                            "TAnalyzer->orderTNodes() : désordre dans les noeuds de template, noeud ouvrant : " .
                            $parentTNode->TNode .", noeud fermant : " . $TNode->TNode . " !");
                    }
                    $parentTNode = array_pop($previousParentsTNode);
                    if($parentTNode->is(TNodeLabel::IF)) {
                        $parentTNode = array_pop($previousParentsTNode);
                    }
                    break;
                case TNodeLabel::PARENT :
                    if(!($parentTNode->is(TNodeLabel::ROOT) && !$parentTNode->hasChildren())) {
                        throw new TSyntaxException(
                            "TAnalyzer->orderTNodes() : un noeud <<parent ...> doit toujours être déclaré en premier 
                             noeud d'un template ! Noeud concerné : " . $TNode->TNode . " !");
                    }
                default :
                    $parentTNode->addChild($TNode);
            }
        }

        if(!$parentTNode->is(TNodeLabel::ROOT)) {
            throw new TSyntaxException("TAnalyzer->orderTNodes() : désordre dans les noeuds de template !");
        }

        return $rootTNode;
    }



    /**
     * @param array $nextTNodeArray
     * @return bool|mixed|TNode
     * @throws TSyntaxException
     */
    private function analyzeTNode(array $nextTNodeArray) : TNode {
        //Analyse d'un noeud de template de type IGNORED.
        if($nextTNodeArray['isIgnoredTNode']) {
            $nextTNode = new TNode(['TNode' => $nextTNodeArray['TNode'], 'label' => TNodeLabel::IGNORED]);
            return $nextTNode;
        //Analyse des autres noeuds de template.
        } else {
            $nextTNode = $nextTNodeArray['TNode'];
            //Récupération du noeud : <<contents>> -> contents.
            $contentsStr = $this->getSimpleTNodeContents($nextTNode);
            //Split sur les espaces.
            $contentsArray = preg_split("/ +/", $contentsStr, -1, PREG_SPLIT_NO_EMPTY);
            if(count($contentsArray) == 0) {
                throw new TSyntaxException("TAnalyzer->analyzeTNode() : noeud de template vide : $nextTNode !");
            } else {
                //Récupération de la structure du noeud.
                $TNodeStructure = [ 'TNode' => $nextTNode ];
                $aContent = strtolower(array_shift($contentsArray));
                if(TNodeLabel::isTNodeLabel($aContent)) {
                    $TNodeStructure['label'] = $aContent;
                } else {
                    throw new TSyntaxException("TAnalyzer->analyzeTNode() : type de noeud de template inconnu : $nextTNode !");
                }
                $TNodeStructure['otherContents'] = $contentsArray;

                //Structure du noeud -> Noeud de template.
                switch ($TNodeStructure['label']) {
                    case TNodeLabel::VALUE :
                    case TNodeLabel::PARENT :
                    case TNodeLabel::ABSTRACT_BLOCK :
                    case TNodeLabel::BLOCK :
                        $nextTNode = $this->analyzeTNode2Contents($TNodeStructure);
                        break;
                    case TNodeLabel::ROUTE :
                        $nextTNode = $this->analyzeTNodeRoute($TNodeStructure);
                        break;
                    case TNodeLabel::IF :
                    case TNodeLabel::IF_NOT :
                        $nextTNode = $this->analyzeTNode2Contents($TNodeStructure, 'condition');
                        break;
                    case TNodeLabel::TERNARY_EXPRESSION :
                        $nextTNode = $this->analyzeTNodeTernary($TNodeStructure);
                        break;
                    case TNodeLabel::ELSE :
                    case TNodeLabel::END_IF :
                    case TNodeLabel::END_IF_NOT :
                    case TNodeLabel::END_BLOCK :
                    case TNodeLabel::END_FOR :
                        $nextTNode = $this->analyzeTNode1Content($TNodeStructure);
                        break;
                    case TNodeLabel::FOR :
                        $nextTNode = $this->analyzeTNodeFor($TNodeStructure);
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
        return substr($TNode, 2, -1);
    }

    /**
     * @param  array $TNodeStructure
     * @return TNode
     * @throws TSyntaxException
     */
    private function analyzeTNode1Content(array $TNodeStructure) : TNode {
        $nbOtherContents = count($TNodeStructure['otherContents']);
        if($nbOtherContents != 0) {
            throw new TSyntaxException(
                'TAnalyzer->analyzeTNode1Content() : nombre de propriétés incorrect dans ce noeud : ' . $TNodeStructure['TNode'].  ' !');
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
     * @throws TSyntaxException
     */
    private function analyzeTNode2Contents(array $TNodeStructure, string $keyProperty = 'name') : TNode {
        $nbOtherContents = count($TNodeStructure['otherContents']);
        if($nbOtherContents != 1) {
            throw new TSyntaxException(
                'TAnalyzer->analyzeTNode2Contents() : nombre de propriétés incorrect dans ce noeud : ' . $TNodeStructure['TNode'].  ' !');
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
     * @throws TSyntaxException
     */
    private function analyzeTNodeRoute(array $TNodeStructure) : TNode {
        $nbOtherContents = count($TNodeStructure['otherContents']);
        if($nbOtherContents != 1) {
            throw new TSyntaxException(
                'TAnalyzer->analyzeTNodeRoute() : nombre de propriétés incorrect dans ce noeud : ' . $TNodeStructure['TNode'].  ' !');
        } else {
            $contents = implode("", $TNodeStructure['otherContents']);
            $contents = preg_split('/:{1}/', $contents, -1, PREG_SPLIT_NO_EMPTY);
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
     * @throws TSyntaxException
     */
    private function analyzeTNodeFor(array $TNodeStructure) : TNode {
        $nbOtherContents = count($TNodeStructure['otherContents']);
        if($nbOtherContents == 0) {
            throw new TSyntaxException(
                'TAnalyzer->analyzeTNodeFor() : nombre de propriétés incorrect dans ce noeud : ' . $TNodeStructure['TNode'].  ' !');
        } else {
            $contents = implode("", $TNodeStructure['otherContents']);
            $contents = preg_split('/:{1}/', $contents, -1, PREG_SPLIT_NO_EMPTY);
            $TNodeStructure['set'] = $contents[0];
            $TNodeStructure['element'] = $contents[1];
            unset($TNodeStructure['otherContents']);
            $TNode = new TNode($TNodeStructure);
            return $TNode;
        }
    }

    /**
     * @param array $TNodeStructure
     * @return TNode
     * @throws TSyntaxException
     */
    private function analyzeTNodeTernary(array $TNodeStructure) : TNode {
        $nbOtherContents = count($TNodeStructure['otherContents']);
        if($nbOtherContents == 0) {
            throw new TSyntaxException(
                'TAnalyzer->analyzeTNodeTernary() : nombre de propriétés incorrect dans ce noeud : ' . $TNodeStructure['TNode'].  ' !');
        } else {
            $contents = $this->getSimpleTNodeContents($TNodeStructure['TNode']);
            $contents = preg_split('/^ *'. TNodeLabel::TERNARY_EXPRESSION .' +/', $contents, -1, PREG_SPLIT_NO_EMPTY);
            $contents = preg_split('/ *\?{1} */', $contents[0], -1, PREG_SPLIT_NO_EMPTY);
            $TNodeStructure['condition'] =  $contents[0];
            $contents = preg_split('/ *:{1} */', $contents[1], -1, PREG_SPLIT_NO_EMPTY);
            $TNodeStructure['then'] =  $contents[0];
            $contents = preg_split('/ *$/', $contents[1], -1, PREG_SPLIT_NO_EMPTY);
            $TNodeStructure['else'] = $contents[0];
            unset($TNodeStructure['otherContents']);
            $TNode = new TNode($TNodeStructure);
            return $TNode;
        }
    }
}