<?php

namespace Simplifying;


abstract class Template
{
    public function __construct()
    {
        $this->render();
    }

    public function render() {
        $content = $this->toHtml();
        View::render($content);
    }



    private function toHtml() {
        $hierarchy = $this->getHierarchy();


        $superTemplate = Template::newTemplate(array_pop($hierarchy));
        $content = $superTemplate->content();


        for($i = count($hierarchy) - 1; $i >= 0; $i--) {
            $template = Template::newTemplate( $hierarchy[$i]);
            $templateContent = $template->content();

            do {
                $implementedMacros = [];
                $matches = preg_match('/\{\{[a-zA-Z0-9]*\}\}/', $templateContent, $implementedMacros);
                if($matches) {
                    $implementedMacro = $implementedMacros[0];
                    $implementedMacroName = substr($implementedMacro, 2, -2);

                    $matches = [];
                    preg_match("/$implementedMacro(.|\n)*\{\{\/$implementedMacroName\}\}/", $templateContent, $matches);
                    $contentImplemented = $matches[0];
                    $templateContent = str_replace($contentImplemented, "", $templateContent);

                    $contentImplemented = str_replace("$implementedMacro", "", $contentImplemented);
                    $contentImplemented = str_replace("{{/$implementedMacroName}}", "", $contentImplemented);

                    $content = str_replace("[[$implementedMacroName]]", $contentImplemented, $content);
                }
            } while ($matches);
        }


        do {
            $unimplementedMacros = [];
            $matches = preg_match("/(\[\[[a-zA-Z0-9]*\]\])+/", $content, $unimplementedMacros);
            if($matches) {
                $unimplementedMacro = $unimplementedMacros[0];
                $unimplementedMacroName = substr($unimplementedMacro, 2, -2);
                $content = str_replace("$unimplementedMacro",
                    "<br><br>/!\ unimplemented macro : $unimplementedMacroName /!\\", $content);
            }
        } while ($matches);


        return $content;
    }



    private function getHierarchy() {
        return Template::getHierarchyHelper(get_class($this));
    }

    private static function getHierarchyHelper($className, $hierarchy = []) {
        if($className == 'Simplifying\Template') {
            return $hierarchy;
        } else {
            $hierarchy[] = $className;
            $parentClassName = get_parent_class($className);
            return Template::getHierarchyHelper($parentClassName, $hierarchy);
        }
    }



    private static function newTemplate($className) {
        return (new \ReflectionClass($className))->newInstanceWithoutConstructor();
    }



    private static function implementsMacros($content, $templateContent) {
        $implementedMacros = [];
        $matches = preg_match('/\{\{[a-zA-Z0-9]*\}\}/', $templateContent, $implementedMacros);

        if(!$matches) {
            return $content;
        } else {
            $implementedMacro = $implementedMacros[0];
            $implementedMacroName = substr($implementedMacro, 2, -2);

            $matches = [];
            preg_match("/$implementedMacro(.|\n)*\{\{\/$implementedMacroName\}\}/", $templateContent, $matches);
            $contentImplemented = $matches[0];
            $templateContent = str_replace($contentImplemented, "", $templateContent);

            $contentImplemented = str_replace("$implementedMacro", "", $contentImplemented);
            $contentImplemented = str_replace("{{/$implementedMacroName}}", "", $contentImplemented);

            $content = str_replace("[[$implementedMacroName]]", $contentImplemented, $content);

            Template::implementsMacros($content, $templateContent);
        }
    }



    public abstract function content();
}