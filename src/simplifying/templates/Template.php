<?php

namespace simplifying\templates;

use simplifying\PathManager;

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
     * Template constructor.
     * @param string $name
     * @param array $externalParameters
     */
    public function __construct(string $name, array $externalParameters = [])
    {
        $this->name = $name;
        $this->externalParameters = $externalParameters;
        Template::initialiseRootTAbsolutePath();
    }

    /**
     * Initialiser le chemin absolu du répertoire racine des templates.
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
     * Parser le template et l'envoyer au navigateur.
     *
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
     * Parser le template et l'envoyer au navigateur.
     *
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
     * @param string $TName
     * @return string
     */
    private function getTAbsolutePath(string $TName) : string {
        return Template::$ROOT_T_ABSOLUTE_PATH . $TName . Template::EXTENSION_T;
    }



    /**
     * Parser un template en arbre syntaxique puis en langages web (html, etc.).
     *
     * Processus de la méthode :
     * template <=> arbre(s) <=> langages web (html, etc.).
     *
     * @return string
     * @throws TSyntaxException
     * @throws TVariableException
     */
    public function parse() : string {
        //Parsing template -> hierarchie d'arbres.
        $trees = $this->parseTemplateInHierarchyOfTrees();
        //Merge / fusion des arbres.
        $tree = array_shift($trees);
        foreach($trees as $key => $childTree) {
            $tree = $this->mergeTrees($tree, $childTree);
        }
        //Parsing arbre -> langages web.
        $parsedTContent = $this->parseTreeInWebLanguages($tree);
        return $parsedTContent;
    }

    /**
     * Parser un template en un tableau d'arbres syntaxiques,
     * chaque arbre correspond à un template de la hierarchie du template this.
     *
     * @throws TSyntaxException
     */
    private function parseTemplateInHierarchyOfTrees() : array {
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
     * Merger deux arbres syntaxiques (un arbre parent et un arbre enfant).
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

    /**
     * Parser un arbre syntaxique en langages web (html, etc.).
     *
     * @param TNode $tree
     * @return string
     * @throws TSyntaxException
     * @throws TVariableException
     */
    private function parseTreeInWebLanguages(TNode $tree) : string {
        $parsingContent = TParser::parseTreeInWebLanguages($tree, $this->externalParameters);
        return $parsingContent;
    }
}