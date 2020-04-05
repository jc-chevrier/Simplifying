<?php

namespace simplifying;

/**
 * Classe Router.
 */
class Router {
    /**
     * Racine du serveur.
     */
    private $dir_root, $file_root;
    /**
     * Route courante.
     */
    private $current_uri;
    /**
     * Route du serveur.
     */
    private $routes;
    /**
     * Un singleton router.
     */
    private static $router;



    private function __construct() {
        $this->previous_uri = null;
        $this->current_uri = null;

        $root = explode('/', $_SERVER['SCRIPT_NAME']);
        $this->dir_root = $root[1];
        $this->file_root = $root[2];

        $this->route( '/error', '             
             <html>
                 <body>
                        <div>
                              Page inexistante sur le serveur.
                        </div>   
                 </body>
             </html>');
    }



    /**
     * Obtenir le singleton router de l'extérieur.
     */
    public static function getInstance() {
        if(Router::$router == null) {
            Router::$router = new Router();
        }
        return Router::$router;
    }



    /**
     * Effectuer l'action correspondante à la route courante.
     */
    public function go() {
        $this->update();
        if(isset($this->routes[$this->current_uri])) {
            $this->routes[$this->current_uri]();
        } else {
            $this->routes['/error']();
        }
    }

    /**
     * Mettre à jour la route courante.
     */
    private function update() {
        $tmp = explode($this->dir_root, $_SERVER['REQUEST_URI'])[1];
        $tmp = explode($this->file_root,  $tmp);
        $tmp = $tmp[count($tmp) - 1];

        $tmp = explode("?",  $tmp);
        $tmp = $tmp[0];

        $this->current_uri = $tmp;
    }



    /**
     * Ajouter une route au serveur.
     */
    public function route($uri, $serverResponse) {
        if(is_callable($serverResponse)) {
            $this->routes[$uri] = $serverResponse;
        } else {
            if(class_exists($serverResponse)) {
                $this->routes[$uri] = function() use ($serverResponse) {
                    (new \ReflectionClass($serverResponse))->newInstance();
                };
            } else {
                $this->routes[$uri] = function() use ($serverResponse) {
                    View::render($serverResponse);
                };
            }
        }
    }

    /**
     * Changer la route d'érreur du serveur.
     */
    public function routeError($serverResponseForError){
        $this->route("/error", $serverResponseForError);
    }

    /**
     * Rediriger vers une autre route.
     */
    public function redirect($uri) {
        if(isset($this->routes[$uri])) {
            $this->routes[$uri]();
        }
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
