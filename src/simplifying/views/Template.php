<?php

namespace simplifying\views;

use \simplifying\routes\Router as Router;
use \simplifying\Util as util;

/**
 * Classe Template.
 *
 * Concept :
 * [[MACRO_NAME]]                                              -> macro non-implémentée.
 *
 * {{MACRO_NAME}}  ... {{\MACRO_NAME}}                         -> macro implénentée.
 *
 * %%DOMAIN_VALUE:NAME_VALUE%%                                 -> macro de valeur.
 *
 * DOMAIN_VALUE appartient à {routes, route, post, get, values}.
 * routes:key:...   -> obtenir une route du serveur
 * route:key        -> obtenir un paramètre de la route courante
 * post:key         -> obtenir une valeur de $_POST
 * get:key          -> obtenir une valeur de $_GET
 * values:key       -> obtenir une valeur de values
 * params:key       -> obtenir une valeur de parameters
 *
 * %%routes:NAME_ROUTE:PARAMETER_0:...:PARAMETER_N-1%%         -> macro de route.
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
    const markupUnimplementedMacro = "\[\[[a-zA-Z0-9-_]+\]\]";
    /**
     * Balisage pour signaler une macro
     * implémentée.
     *
     * C'est une expression régulière.
     */
    const markupImplementedMacro = "\{\{[a-zA-Z0-9-_]+\}\}";
    /**
     * Balisage pour signaler une macro
     * de valeur.
     *
     * C'est une expression régulière.
     */
    const markupValueMacro = "%%[a-zA-Z0-9-_]+(:[a-zA-Z0-9-_]+)+%%";
    /**
     * Attribut fourni pour stocker à volonté des valeurs internes
     * de tout type à utilité pour le template.
     *
     * Bonne utilisation et surtout seule utilisation possible :
     * passer de l'intérieur (via définition de la méthode Template->content).
     */
    private $values = [];
    /**
     * Attribut fourni pour stocker à volonté des paramètres externes
     * de tout type à utilité pour le template.
     *
     * Bonne initialisation et surtout seule initialisation possible :
     * passer de l'extérieur via le constructeur.
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

        //Pour les macro-valeur, on les remplace par leur valeur.
        $content = Template::implementsValueMacros($content, $values, $this->parameters);

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
        //Cas trivial.
        if($className == __CLASS__) {
            return $hierarchy;
        //Cas récursif.
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
        $markupImplementedMacro = Template::markupImplementedMacro;
        $matches = preg_match("/$markupImplementedMacro/", $templateContent, $implementedMacros);
        //Cas trivial.
        if(!$matches) {
            return $content;
         //Cas récursif.
        } else {
            $implementedMacro = $implementedMacros[0];
            $implementedMacroName = Template::getMacroName($implementedMacro);

            $contentsImplemented = [];
            preg_match("/\{\{$implementedMacroName\}\}.*\{\{\/$implementedMacroName\}\}/s", $templateContent, $contentsImplemented);
            $contentImplemented =  $contentsImplemented[0];
            $templateContent = Util::removeOccurrences($contentImplemented, $templateContent);

            $contentImplemented = Util::removeOccurrences([$implementedMacro, "{{/$implementedMacroName}}"], $contentImplemented);
            $content = str_replace("[[$implementedMacroName]]", $contentImplemented, $content);

            return Template::implementsMacros($content, $templateContent);
        }
    }

    /**
     * Implémenter les macros de valeurs.
     */
    private static function implementsValueMacros($content, $values, $parameters) {
        $valueMacro = [];
        $markupValueMacro = Template::markupValueMacro;
        $matches = preg_match("/$markupValueMacro/", $content,  $valueMacro);
        //Cas trivial.
        if(!$matches) {
            return $content;
        //Cas récursif.
        } else {
            $valueMacro  = $valueMacro[0];
            $valueMacroContent = Template::getMacroName($valueMacro);
            $valueMacroContents = explode(":", $valueMacroContent);

            $firstContent = $valueMacroContents[0];
            $secondContent = $valueMacroContents[1];

            $value = null;
            switch($firstContent) {
                case DomainValueMacro::ROUTES :
                    $routeParameters = array_slice($valueMacroContents, 2, count($valueMacroContents) - 1);
                    $value = Template::$router->getRoute($secondContent, $routeParameters);
                    break;

                case DomainValueMacro::ROUTE :
                    $value = Template::$router->currentRoute->$secondContent;
                    break;

                case DomainValueMacro::GET :
                    $value = $_GET[$secondContent];
                    break;

                case DomainValueMacro::POST :
                    $value = $_POST[$secondContent];
                    break;

                case DomainValueMacro::VALUES :
                    $value = $values[$secondContent];
                    break;

                case DomainValueMacro::PARAMS :
                    $value = $parameters[$secondContent];
                    break;

                default :
                    $value = "";
            }
            //Remplacement de la macro par sa valeur.
            $content = str_replace($valueMacro, $value, $content);
            //Appel récursif.
            return Template::implementsValueMacros($content, $values, $parameters);
        }
    }

    /**
     * Gerer les macros non-implémentées.
     */
    private static function manageUnimplementedMacros($content) {
        $unimplementedMacros = [];
        $markupUnimplementedMacro = Template::markupUnimplementedMacro;
        $matches = preg_match("/$markupUnimplementedMacro/", $content, $unimplementedMacros);
        //Cas trivial.
        if(!$matches) {
            return $content;
        //Cas récursif.
        } else {
            $unimplementedMacro = $unimplementedMacros[0];
            $content = Util::removeOccurrences($unimplementedMacro, $content);
            return Template::manageUnimplementedMacros($content);
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
        } else {
            if(isset($this->parameters[$name])) {
                return $this->parameters[$name];
            } else {
                if(isset($this->values[$name])) {
                    return $this->values[$name];
                }
            }
        }
        return false;
    }
}