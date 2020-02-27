<?php

namespace Simplifying;


class Template
{
    private $css = [];
    private $scripts = [];


    public function render() {
        $content = $this->toHtml();
        View::render($content);
    }



    public function css($css) {
        $this->css[] = "\n<style>\n$css\n</style>";
    }

    public function css_file($css) {
        $this->css[] = "\n<link href=$css rel=stylesheet type=text/css media=all>";
    }




    public function jqueryScript() {
        $this->scripts[] = "\n<script src=https://code.jquery.com/jquery-3.3.1.min.js> </script>";
    }

    public function script($script) {
        $this->scripts[] = "\n<script>\n$script\n</script>";
    }

    public function script_file($script) {
        $this->scripts[] = "\n<script type=module src=$script></script>";
    }



    private function toHtml() {
        $hierarchy = $this->getHierarchy();

        $superTemplate = (new \ReflectionClass(array_pop($hierarchy)))->newInstanceWithoutConstructor();
        $content = $superTemplate->content();


        for($i = count($hierarchy) - 1; $i >= 0; $i--) {
            $element = $hierarchy[$i];
            $template = (new \ReflectionClass($element))->newInstanceWithoutConstructor();
            $templateContent = $template->content();

            do {
                $implementedMacros = [];
                $matches = preg_match('/\{\{[a-zA-Z0-9]*\}\}/', $templateContent, $implementedMacros,PREG_UNMATCHED_AS_NULL);
                if($matches) {
                    $implementedMacro = $implementedMacros[0];
                    $implementedMacroName = substr($implementedMacro, 2, -2);

                    $matches = [];
                    preg_match("/$implementedMacro(.|\n)*\{\{\/$implementedMacroName\}\}/", $templateContent,
                        $matches, PREG_UNMATCHED_AS_NULL);
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
            $matches = preg_match("/(\[\[[a-zA-Z0-9]*\]\])+/", $content, $unimplementedMacros, PREG_UNMATCHED_AS_NULL);
            if($matches) {
                $unimplementedMacro = $unimplementedMacros[0];
                $unimplementedMacroName = substr($unimplementedMacro, 2, -2);
                $content = str_replace("$unimplementedMacro","<br><br>/!\ unimplemented macro : $unimplementedMacroName /!\\", $content);
            }
        } while ($matches);


        return $content;
    }



    private function getHierarchy() {
        return Template::getHierarchyHelper(get_class($this));
    }

    private static function getHierarchyHelper($class, $hierarchy = []) {
        if($class == 'Simplifying\Template') {
            return $hierarchy;
        } else {
            $hierarchy[] = $class;
            $parentClass = get_parent_class($class);
            return Template::getHierarchyHelper($parentClass, $hierarchy);
        }
    }



    public function content() {}
}