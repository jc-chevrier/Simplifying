<?php

namespace simplifying;


/**
 * Classe Route.
 *
 * Concept :
 * Route modèle -> /#id
 * Route intermédiaire -> /#
 * Route effective -> /#01
 *
 * La forme intermédiaire sert à faire la comparaison
 * entre la forme modèle et la forme effective.
 */
class Route
{
    /**
     * Paramètres de la route
     * (s'il y en a).
     */
    private $parameters;
    /**
     * Routes modèle et route effective.
     */
    private $templateRoute, $effectiveRoute;




    public function __construct($templateRoute, $effectiveRoute) {
        $this->templateRoute = $templateRoute;
        $this->effectiveRoute = $effectiveRoute;
        $this->learnParameters();
    }




    /**
     * Savoir si une route a des paramètres.
     */
    public static function hasParameters($route) {
        if(is_bool(strpos($route, "#"))) {
            return false;
        }
        return true;
    }




    /**
     * Mémoriser les paramètres de la route.
     */
    private function learnParameters() {
        $this->parameters = [];

        if(Route::hasParameters($this->templateRoute)) {
            Route::learnParametersHelper($this->templateRoute, $this->effectiveRoute, $this->parameters);
        }
    }

    /**
     * Helper pour mémoriser les paramètres d'une route.
     */
    public static function learnParametersHelper($templateRoute, $effectiveRoute, $parameters) {
        $parameterName = [];
        $parameterValue = [];

        $matches = preg_match("/\/#.*\//", $templateRoute,  $parameterName);
        preg_match("/\/#.*\//", $effectiveRoute, $parameterValue);

        if($matches) {
            $parameterName = $parameterName[0];
            $parameterName = strpos($parameterName, 1);
            $parameterValue = $parameterValue[0];
            $parameterValue = strpos($parameterValue, 1);

            $parameters[$parameterName] = $parameterValue;

            $templateRoute = Util::removeOccurrences($parameterName, $templateRoute);
            $effectiveRoute = Util::removeOccurrences($parameterValue, $effectiveRoute);

            Route::learnParametersHelper($templateRoute, $effectiveRoute, $parameters);
        }
    }



    /**
     * Obtenir la forme intermédiaire pour une route
     * effective ou une route modèle.
     */
    public static function toTransitionnalForm($route) {
        $parameter = [];
        $matches = preg_match("/\/#.*\//", $route,$parameter);

        if(!$matches) {
            return $route;
        } else {
            $parameter = $parameter[0];
            $parameter = strpos($parameter, 1);
            $route = Util::removeOccurrences($parameter, $route);
            return Route::toTransitionnalForm($route);
        }
    }



    public function __get($name)
    {
        if(isset($this->parameters[$name])) {
            return $this->parameters[$name];
        } else {
            if($this->$name) {
                return $this->$name;
            } else {
                return false;
            }
        }
    }
}