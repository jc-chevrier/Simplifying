<?php

namespace simplifying\routes;

/**
 * Classe Route.
 *
 * /uripart1/uripart2/{id}/{id2} -> route modèle
 * /uripart1/uripart2/01/03 -> route effective
 *
 * {id} -> paramètre modèle
 * /01 -> paramètre effectif
 */
class Route
{
    /**
     * Balisage dans une route pour signaler
     * un paramètre.
     *
     * C'est une expression régulière.
     */
    private static $markupParameter =  "{.*}";
    /**
     * Route modèle et route effcetive.
     *
     * Route modèle : /01/
     * Route effective : /#id/
     */
    private $templateRoute, $effectiveRoute;
    /**
     * Paramètres d'une Route.
     */
    private $parameters;
    /**
     * Alias de la route.
     */
    private $alias;
    /**
     * Action de la route.
     */
    private $action;



    public function __construct($templateRoute, $action) {
        $this->templateRoute = $templateRoute;
        $this->action = $action;
    }



    /**
     * Declarer un alias pour une route.
     */
    public function alias($alias) {
        $this->alias = $alias;
    }



    /**
     * Rendre une route effective.
     *
     * @param $effectiveRoute       La route effective.
     *                              /uripart1/nuripart2/01
     *
     * @param $nodes                Les noeuds de la route modèle.
     *                              /uripart1 -> /uripart2 -> /id
     */
    public function beginEffective($effectiveRoute, $nodes) {
        $this->effectiveRoute = $effectiveRoute;
        $values = Route::toUriParts($effectiveRoute);
        foreach($nodes as $index => $node) {
            $value = array_shift($values);
            if($node->type() == NodeType::PARAMETER_NODE) {
                $this->parameters[Route::getParamaterName($node->value)] = $value;
            }
        }
    }

    /**
     * Executer l'action de la route.
     */
    public function go() {
        $action = $this->action;
        $action();
    }




    /**
     * Récupérer le nom d'un paramètre.
     *
     * {id} -> id
     *
     * @param $parameter    {id}
     *
     * @return string        id
     */
    public static function getParamaterName($parameter) {
        return substr($parameter, 1, -1);
    }

    /**
     * Savoir si un string contient un paramètre.
     */
    public static function containsParameter($string) {
        $markupParameter = Route::$markupParameter;
        $matches = preg_match("/$markupParameter/", $string);

        if($matches) {
            return true;
        }
        return false;
    }




    /**
     * Récuperer un tableau des parties de l'URI.
     */
    public static function toUriParts($route) {
        $uriParts = explode("/", $route);
        unset($uriParts[array_search("", $uriParts)]);
        return $uriParts;
    }




    public function __get($name)
    {
        if (isset($this->$name)) {
            return $this->$name;
        } else {
            if(isset($this->parameters[$name])) {
                return $this->parameters[$name];
            }
        }
        return false;
    }
}