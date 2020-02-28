<?php

namespace simplifying;

abstract class Template
{
    //Bonne utilisation : passer de l'extérieur (constructeur).
    private $parameters = [];
    //Bonne utilisation : passer davantage de l'intérieur mais possible de l'extérieur (constructeur).
    private $values = [];
    public static $router;



    public function __construct($parameters = [], $values = [])
    {
        $this->parameters = $parameters;
        $this->values = $values;
        $this->render();
    }



    public static function initialiseStaticParameters() {
        Template::$router = Router::getInstance();
    }



    public function render() {
        //Initialiser les paramètres statiques.
        Template::initialiseStaticParameters();
        //Transformer une template en html.
        $content = $this->toHtml();
        //On envoie la template.
        View::render($content);
    }



    private function toHtml() {
        //On récupère la hiérarchie des templates.
        $hierarchy = $this->getHierarchy();

        //On récupère le template de la super classe.
        $superTemplate = Template::newTemplate(array_pop($hierarchy));
        //On récupère le contenu du template.
        $content = $superTemplate->content();
        //On récupère les valeurs du template.
        $values =  $superTemplate->values;

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
        for($i = 0; $i < count($this->parameters); $i++) {
            $values["p$i"] = $this->parameters[$i];
        }
        //Pour les macro-valeur, on les remplace par leur valeur.
        $content = Template::implementsValueMacros($content, $values);

        //Pour les macros non-implémentées, on les remplace par mot-vide.
        $content = Template::manageUnimplementedMacros($content);

        return $content;
    }



    private function getHierarchy() {
        return Template::getHierarchyHelper(get_class($this));
    }

    private static function getHierarchyHelper($className, $hierarchy = []) {
        if($className == 'simplifying\Template') {
            return $hierarchy;
        } else {
            $hierarchy[] = $className;
            $parentClassName = get_parent_class($className);
            return Template::getHierarchyHelper($parentClassName, $hierarchy);
        }
    }



    private static function newTemplate($className) {
        $reflection = new \ReflectionClass($className);
        $template = $reflection->newInstanceWithoutConstructor();
        return $template;
    }



    private static function implementsMacros($content, $templateContent) {
        $implementedMacros = [];
        $matches = preg_match('/\{\{[a-zA-Z0-9-]+\}\}/', $templateContent, $implementedMacros);

        if(!$matches) {
            return $content;
        } else {
            $implementedMacro = $implementedMacros[0];
            $implementedMacroName = substr($implementedMacro, 2, -2);

            $contentsImplemented= [];
            preg_match("/\{\{$implementedMacroName\}\}(.|\n)*\{\{\/$implementedMacroName\}\}/", $templateContent,
           $contentsImplemented);
            $contentImplemented =  $contentsImplemented[0];
            $templateContent = Util::removeOccurrences($contentImplemented, $templateContent);

            $contentImplemented = Util::removeOccurrences([$implementedMacro, "{{/$implementedMacroName}}"], $contentImplemented);
            $content = str_replace("[[$implementedMacroName]]", $contentImplemented, $content);

            return Template::implementsMacros($content, $templateContent);
        }
    }



    private static function manageUnimplementedMacros($content) {
        $unimplementedMacros = [];
        $matches = preg_match("/\[\[[a-zA-Z0-9-]+\]\]/", $content, $unimplementedMacros);

        if(!$matches) {
            return $content;
        } else {
            $unimplementedMacro = $unimplementedMacros[0];
            $content = Util::removeOccurrences($unimplementedMacro, $content);//str_replace($unimplementedMacro, "pas implémenté", $content);
            return Template::manageUnimplementedMacros($content);
        }
    }



    private static function implementsValueMacros($content, $values) {
        $implementedMacros = [];
        $matches = preg_match("/%%[a-zA-Z0-9-]+%%/", $content, $implementedMacros);

        if(!$matches) {
            return $content;
        } else {
            $implementedMacro = $implementedMacros[0];
            $implementedMacroName = substr($implementedMacro, 2, -2);

            $implementedContent = Template::$router->post($implementedMacroName);
            if(is_bool($implementedContent)) {
                $implementedContent = Template::$router->get($implementedMacroName);
                if(is_bool($implementedContent)) {
                    if(isset($values[$implementedMacroName])) {
                        $implementedContent = $values[$implementedMacroName];
                    } else {
                        $implementedContent = "";
                    }
                }
            }
            $content = str_replace($implementedMacro, $implementedContent, $content);

            return Template::implementsValueMacros($content, $values);
        }
    }



    public function value($key, $value) {
        $this->values[$key] = $value;
    }



    public abstract function content();



    public function __get($name)
    {
        if(isset($this->$name)) {
            return $this->$name;
        }
        return false;
    }
}