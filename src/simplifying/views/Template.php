<?php

namespace simplifying\views;

use \simplifying\routes\Router as Router;
use \simplifying\Util as util;

/**
 * Classe Template.
 *
 *
 * Concept :
 * [[MACRO_NAME]]                       -> macro non-implémentée.
 *
 * {{MACRO_NAME}}  ... {{\MACRO_NAME}}  -> macro implénentée.
 *
 * %%NAME_VALUE%%                       -> macro de valeur.
 *
 *
 * Une valeur peut-être retrouvée dans :
 * $_GET, $_POST, $Template->parameters, Template->values,
 * Router->currentRoute->parameters.
 *
 * Attention ! il faut toujours faire attention à ce que
 * les clés soit différentes parmis tous les ensembles dont
 * peut provenir une valeur.
 *
 * @author CHEVRIER Jean-Christophe.
 */
abstract class Template
{
    /**
     * Balisage pour signaler une macro
     * non-implémentée.
     *
     * C'est une expression régulière.
     */
    const markupNotImplementedMacro = "\[\[[a-zA-Z0-9-]+\]\]";
    /**
     * Balisage pour signaler une macro
     * implémentée.
     *
     * C'est une expression régulière.
     */
    const markupImplementedMacro = "\{\{[a-zA-Z0-9-]+\}\}";
    /**
     * Balisage pour signaler une macro
     * de valeur.
     *
     * C'est une expression régulière.
     */
    const markupValueMacro = "%%[a-zA-Z0-9-]+%%";
    /**
     * Attribut fourni pour stocker à volonté des valeurs internes
     * de tout type à utilité pour le template.
     *
     * Bonne utilisation et surtout seule utilisation possible :
     * passer de l'intérieur (via définition de la méthode Template->content).
     *
     * %%NAME_VALUE%% est une macro permettant d'obtenir une valeur
     * interne dans le contenu de son template.
     * Avec NAME_VALUE un indice/clé du tableau values.
     */
    private $values = [];
    /**
     * Attribut fourni pour stocker à volonté des paramètres externes
     * de tout type à utilité pour le template.
     *
     * Bonne initialisation et surtout seule initialisation possible :
     * passer de l'extérieur via le constructeur.
     *
     * %%NAME_PARAMETER%% est une macro permettant d'obtenir un paramètre
     * externe dans le contenu d'un template.
     * Avec NAME_PARAMETER un indice/clé du tableau parameters.
     *
     * Les paramètres une fois passés de l'extérieur, sont en interne
     * ajoutés aux valeurs $values.
     */
    private $parameters = [];
    /**
     * router pour accéder au contexte général du serveur.
     */
    private static $router;


    /**
     * Instancier un template, et lancer son fonctionnement
     * (conversion en html et envoi au navigateur).
     */
    public function __construct($parameters = [])
    {
        $this->parameters = $parameters;
        $this->values = [];
        $this->render();
    }



    public static function initialiseStaticParameters() {
        Template::$router = Router::getInstance();
    }


    /**
     * Convertir un template en code html, et envoyer
     * le code html à un navigateur via un fichier viruel
     * (HEREDOC).
     */
    public function render() {
        //Initialiser les paramètres statiques.
        Template::initialiseStaticParameters();
        //Transformer une template en html.
        $content = $this->toHtml();
        //On envoie la template.
        View::render($content);
    }


    /**
     * Convertir un template en code html.
     */
    private function toHtml() {
        //On récupère la hiérarchie des templates.
        $hierarchy = $this->getHierarchy();

        //On récupère le template de la super classe.
        $superTemplate = Template::newTemplate(array_pop($hierarchy));
        //On récupère le contenu du template.
        $content = $superTemplate->content();
        //On récupère les valeurs du template.
        $values = $superTemplate->values;

        //On parcours la hiérarchie des templates de la super classe jusqu'à la classe de this.
       for($i = count($hierarchy) - 1; $i >= 0; $i--) {
            //On récupère le template.
            $template = $i == 0 ? $this : Template::newTemplate($hierarchy[$i]);
            //On récupère le contenu du template.
            $templateContent = $template->content();
            //On récupère les valeurs du template.
            $values = array_merge($values, $template->values);
            //Pour les macros implémentées, on les remplace par leur contenu.
            $content = Template::implementsMacros($content, $templateContent);
        }

        //On ajoute les paramètres du template this aux valeurs.
        foreach($this->parameters as $parameterKey => $parameterValue) {
            $values[$parameterKey] = $parameterValue;
        }
        //Pour les macro-valeur, on les remplace par leur valeur.
        $content = Template::implementsValueMacros($content, $values);

        //Pour les macros non-implémentées, on les remplace par mot-vide.
        $content = Template::manageUnimplementedMacros($content);

        return $content;
    }


    /**
     * Obtenir la hierarchie de templates du template this.
     */
    private function getHierarchy() {
        return Template::getHierarchyHelper(get_class($this));
    }

    /**
     * Obtenir la hierarchie de templates d'un template.
     */
    private static function getHierarchyHelper($className, $hierarchy = []) {
        if($className == __CLASS__) {
            return $hierarchy;
        } else {
            $hierarchy[] = $className;
            $parentClassName = get_parent_class($className);
            return Template::getHierarchyHelper($parentClassName, $hierarchy);
        }
    }


    /**
     * Créer une instance à partir d'un nom de classe de template.
     */
    private static function newTemplate($className) {
        $reflection = new \ReflectionClass($className);
        $template = $reflection->newInstanceWithoutConstructor();
        return $template;
    }


    /**
     * Récupérer le nom d'une macro.
     */
    private static function getMacroName($macro) {
        $macroName = substr($macro, 2, -2);
        return $macroName;
    }


    /**
     * Implementer les macros de $content avec les c
     * ontenus des macros dans $templateContent.
     */
    private static function implementsMacros($content, $templateContent) {
        $implementedMacros = [];
        $macroImplementedMacro = Template::markupImplementedMacro;
        $matches = preg_match("/$macroImplementedMacro/", $templateContent, $implementedMacros);

        if(!$matches) {
            return $content;
        } else {
            $implementedMacro = $implementedMacros[0];
            $implementedMacroName = Template::getMacroName($implementedMacro);

            $contentsImplemented = [];
            preg_match("/\{\{$implementedMacroName\}\}(.|\n)*\{\{\/$implementedMacroName\}\}/", $templateContent,
           $contentsImplemented);
            $contentImplemented =  $contentsImplemented[0];
            $templateContent = Util::removeOccurrences($contentImplemented, $templateContent);

            $contentImplemented = Util::removeOccurrences([$implementedMacro, "{{/$implementedMacroName}}"], $contentImplemented);
            $content = str_replace("[[$implementedMacroName]]", $contentImplemented, $content);

            return Template::implementsMacros($content, $templateContent);
        }
    }



    /**
     * Gerer les macros non-implémentées.
     */
    private static function manageUnimplementedMacros($content) {
        $unimplementedMacros = [];
        $markupNotImplementedMacro = Template::markupNotImplementedMacro;
        $matches = preg_match("/$markupNotImplementedMacro/", $content, $unimplementedMacros);

        if(!$matches) {
            return $content;
        } else {
            $unimplementedMacro = $unimplementedMacros[0];
            $content = Util::removeOccurrences($unimplementedMacro, $content);//str_replace($unimplementedMacro, "pas implémenté", $content);
            return Template::manageUnimplementedMacros($content);
        }
    }



    /**
     * Implémenter les macros de valeurs.
     */
    private static function implementsValueMacros($content, $values) {
        $implementedMacros = [];
        $markupValueMacro = Template::markupValueMacro;
        $matches = preg_match("/$markupValueMacro/", $content, $implementedMacros);

        if(!$matches) {
            return $content;
        } else {
            $implementedMacro = $implementedMacros[0];
            $implementedMacroName = Template::getMacroName($implementedMacro);

            //Récuperation d'une valeur dans la route courante.
            $implementedContent = Template::$router->currentRoute->$implementedMacroName;
            //Si rien trouvé dans la route courante.
            if(is_bool($implementedContent)) {
                //Récuperation d'une valeur dans $_POST.
                $implementedContent = Template::$router->post($implementedMacroName);
                //Si rien trouvé dans $_POST.
                if(is_bool($implementedContent)) {
                    //Récuperation d'une valeur dans $_GET.
                    $implementedContent = Template::$router->get($implementedMacroName);
                    //Si rien trouvé dans $_GET.
                    if(is_bool($implementedContent)) {
                        //Récuperation d'une valeur dans values du template.
                        if(isset($values[$implementedMacroName])) {
                            $implementedContent = $values[$implementedMacroName];
                         //Si rien trouvé dans $values.
                        } else {
                            //On remplace par mot-vide si pas de valeurs trouvées.
                            $implementedContent = "";
                        }
                    }
                }
            }

            $content = str_replace($implementedMacro, $implementedContent, $content);

            return Template::implementsValueMacros($content, $values);
        }
    }


    /**
     * Ajouter une valeur interne.
     */
    public function value($key, $value) {
        $this->values[$key] = $value;
    }


    /**
     * Méthode à définir pour écrire un template.
     */
    public abstract function content();



    public function __get($name)
    {
        if(isset($this->$name)) {
            return $this->$name;
        }
        return false;
    }
}