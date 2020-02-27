<?php

namespace Simplifying;

class Router {
    private $dir_root, $file_root;
    private $previous_uri, $current_uri;
    private $routes;
    private static $router;


    private function __construct() {
        $this->previous_uri = null;
        $this->current_uri = null;
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
       if (isset($this->routes[$this->current_uri])) {
           $this->routes[$this->current_uri]();
       } else {
           View::render('
             <hrml>
                 <head>
                 </head>
                 <body>
                        <div>
                              Page inexistante sur le serveur.
                        </div>   
                 </body>
             </hrml>
            ');
       }
    }

    private function update() {
        $uri = explode($this->dir_root, $_SERVER['REQUEST_URI'])[1];
        $uris = explode($this->file_root, $uri);
        $uri = $uris[count($uris) - 1];
        $this->previous_uri =  $this->current_uri;
        $this->current_uri = $uri;
    }

    public function route($uri, $callBack) {
        $this->routes[$uri] = $callBack;
    }


    public function __get($name)
    {
       if(isset($this->$name)) {
           return $this->$name;
       }
       return false;
    }


    public function get($name, $value = null) {
        if($value != null) {
            $_GET[$name] = $value;
        } else {
            if(isset($_GET[$name])) {
                return $_GET[$name];
            }
            return false;
        }
    }

    public function post($name, $value = null) {
        if($value != null) {
            $_POST[$name] = $value;
        } else {
            if(isset($_POST[$name])) {
                return $_POST[$name];
            }
            return false;
        }
    }
}