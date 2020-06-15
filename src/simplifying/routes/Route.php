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
 *
 * @author CHEVRIER Jean-Christophe
 * @package simplifying\routes
 */
class Route
{
    /**
     * Balisage dans une route pour signaler
     * un paramètre.
     *
     * C'est une expression régulière.
     */
    const markupParameter = "\{[a-zA-Z0-9-_]+\}";
    /**
     * Route modèle et route effcetive.
     *
     * Route modèle : /{id}/
     * Route effective : /01/
     */
    private $templateRoute, $effectiveRoute;
    /**
     * Route modèle en noeuds.
     *
     * C'est une structure inetermédiaire qui permet
     * de connaitre la structure sémantique de la route
     * (où sont les paramètres de la route entre autre).
     *
     * Concrètement c'est un tableau de noeuds.
     */
    private $templateRouteNodes;
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


    /**
     * Route constructor.
     * @param $templateRoute
     * @param $templateRouteNodes
     * @param $action
     */
    public function __construct($templateRoute, $templateRouteNodes, $action) {
        $this->templateRoute = $templateRoute;
        $this->templateRouteNodes = $templateRouteNodes;
        $this->action = $action;
        $this->parameters = [];
    }



    /**
     * Declarer un alias pour une route.
     * 
     * @param string $alias
     */
    public function alias(string $alias) {
        foreach(Router::getInstance()->routes as $templateRoute => $route) {
            //Si on a retrouvé la route à partir de l'alias.
            if($route != $this && $route->alias == $alias) {
                throw new \InvalidArgumentException("Route->alias() : cet alias : $alias est déjà utilisé !");
            }
        }
        $this->alias = $alias;
    }



    /**
     * Rendre une route effective.
     *
     * @param string $effectiveRoute       La route effective.
     *                                     /uripart1/nuripart2/01
     */
    public function beginEffective(string $effectiveRoute) : void {
        $this->effectiveRoute = $effectiveRoute;
        //Récupération des parties d'URI de la route effective.
        $values = Route::toUriParts($effectiveRoute);
        //Initialisation des paramètres de la route.
        $this->parameters = [];
        foreach($this->templateRouteNodes as $index => $node) {
            $value = array_shift($values);
            if($node->type == NodeType::PARAMETER_NODE) {
                $this->parameters[$node->value] = $value;
            }
        }
    }

    /**
     * Effectuer l'action de la route.
     */
    public function run() : void {
        call_user_func_array($this->action, $this->parameters);
    }




    /**
     * Récupérer le nom d'un paramètre.
     *
     * {id} -> id
     *
     * @param string $parameter    {id}
     *
     * @return string               id
     */
    public static function getParameterName(string $parameter) : string {
        return substr($parameter, 1, -1);
    }


    /**
     * Savoir si un string contient un paramètre.
     *
     * @param string $string
     * @return bool
     */
    public static function containsParameter(string $string) : bool {
        $matchesFound = preg_match('/' . Route::markupParameter . '/', $string);
        if($matchesFound) {
            return true;
        } else {
            return false;
        }
    }




    /**
     * Récupérer un tableau des parties de l'URI.
     *
     * @param string $route
     * @return array
     */
    public static function toUriParts(string $route) : array {
        $uriParts = preg_split('/\//', $route, -1, PREG_SPLIT_NO_EMPTY);
        return $uriParts;
    }



    /**
     * @param string $name
     * @return bool|mixed
     */
    public function __get(string $name)
    {
        if (isset($this->$name)) {
            return $this->$name;
        } else {
            if(array_key_exists($name, $this->parameters)) {
                return $this->parameters[$name];
            }
        }
        return false;
    }
}