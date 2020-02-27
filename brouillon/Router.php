<?php

namespace simplifying;

class Router {
    private $dir_root, $file_root;
    private $previous_route, $current_route;
    private $routes;
    private static $router;


    private function __construct() {
        $this->previous_route = null;
        $this->current_route = null;
        $root = explode('/', $_SERVER['SCRIPT_NAME']);
        $this->dir_root = $root[1];
        $this->file_root = $root[2];
    }



    public static function getInstance() {
        if(Router::$router == null) {
            Router::$router = new Router();
        }
        return Router::$router;
    }



    public function go() {
       $this->update();
       if (isset($this->routes[$this->current_route])) {
           $this->routes[$this->current_route]['callback']();
       } else {
           (new \formulars\views\ErrorView())->render();
       }
    }

    private function update() {
        $this->previous_route = $this->current_route;

        $route = explode($this->dir_root, $_SERVER['REQUEST_URI'])[1];
        $uris = explode($this->file_root, $route);
        $route = $uris[count($uris) - 1];

        $uris = explode("/", $route);
        $generalRoute = "";
        $routesCandidates = [];
        foreach ($uris as $uri) {
            $GLOBALS[0] = $uri;

            $temp = array_filter($this->routes, function($key) {
                return preg_match($GLOBALS[0], $key) >= 0;
            },ARRAY_FILTER_USE_KEY);

            if(count($temp) == 0) {
                $generalRoute .=  "\\X";
                break;
            } else {
                $routesCandidates = $temp;
                $generalRoute .=  "\\$uri";
            }
        }

        $count = count($this->routes[$generalRoute]['parameters']);
        $parameters = [];
        for ($i = 0; $i < $count; $i++) {
            $parameters[$i] = $uris[$i + 1];
        }


        $this->current_route = $this->routes[$generalRoute];
    }

    public function route($name, $route, $callBack) {
        $uris = explode("/", $route);
        $generalRoute = "";
        $parameters = [];
        foreach ($uris as $uri) {
            $matches = [];
            preg_match('\{.*\}', $uri, $matches);
            if(count($matches) > 0) {
                $parameter = $matches[0];
                $parameters[$parameter] = "";
                $generalRoute .= "\\X";
            } else {
                $generalRoute .= "\\$uri";
            }
        }
        $this->routes[$generalRoute] = ["callback" => $callBack, "parameters" => $parameters];
    }



    public function __get($name) {
       if(isset($this->$name)) {
           return $this->$name;
       }
    }



    public function get($name) {
        if(isset($_GET[$name])) {
            return $_GET[$name];
        }
        return false;
    }

    public function post($name) {
        if(isset($_POST[$name])) {
            return $_POST[$name];
        }
        return false;
    }
}



