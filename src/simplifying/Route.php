<?php

namespace simplifying;

//TODO à terminer.
class Route
{
    private $parameters;
    private $templateRoute, $effectiveRoute;



    public function __construct($templateRoute, $effectiveRoute) {
        $this->templateRoute = $templateRoute;
        $this->effectiveRoute = $effectiveRoute;
        $this->manageParameters();
    }



    private function manageParameters() {
        $this->parameters = [];
        $this->manageParametersHelper($this->templateRoute);
    }

    private function manageParametersHelper($templateRoute) {
        $parameters = [];
        $matches = preg_match("/{.*}/", $templateRoute, $parameters);

        if($matches) {
            $parameter = $parameters[0];
            $parameterName = strpos($parameter, 1, -1);

            $this->parameters[$parameterName] = "";
            $templateRoute = Util::removeOccurrences($parameter, $templateRoute);

            $this->manageParametersHelper($templateRoute);
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