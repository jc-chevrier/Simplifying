<?php

namespace simplifying;

class Router {
    /**
     * Racines de l'url du serveur.
     */
    private $dir_root, $file_root;
    /**
     * Singletion router.
     */
    private static $router;
    /**
     * Listes des routes du serveur et sa route courante.
     */
    private $routes, $currentRoute;




    private function __construct() {
        $this->currentRoute = null;

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
     * Obtenir le singleton router de l'extérieur de la classe.
     */
    public static function getInstance() {
        if(Router::$router == null) {
            Router::$router = new Router();
        }
        return Router::$router;
    }




    /**
     * Executer l'action associée à la route courante.
     * Cette action est en général l'envoi d'une page web au navigateur.
     */
    public function go() {
       $this->update();
       if(isset($this->routes[$this->currentRoute->templateRoute])) {
           $this->routes[$this->currentRoute->templateRoute]();
       } else {
           $this->routes['/error']();
       }
    }

    /**
     * Mettre à jour la route courante du serveur.
     */
    private function update() {
        //Récupérer la partie de l"URI correspondant à la route effective courante.
        $effectiveUri = explode($this->dir_root, $_SERVER['REQUEST_URI'])[1];
        $parts = explode($this->file_root, $effectiveUri);

        //Recupérer la route effective courante du serveur.
        $effectiveRoute = $parts[count($parts) - 1];
        //Retrouver la route modèle correspondant à la route effective.
        $templateRoute = $this->findTemplateRoute($effectiveRoute);

        //Initialiser un objet Route.
        $this->currentRoute = new Route($templateRoute, $effectiveRoute);
    }

    /**
     * Rechercher la route modèle correspondant à la route effective.
     *
     * Concept :
     * Route modèle -> /#id
     * Route inetrmédiaire -> /#
     * Route effective -> /#01
     */
    private function findTemplateRoute($effectiveRoute) {
        if(Route::hasParameters($effectiveRoute)) {
            $transtionnalForm = Route::toTransitionnalForm($effectiveRoute);
            foreach($this->routes as $route => $action) {
                if(Route::toTransitionnalForm($route) === $transtionnalForm) {
                    return $route;
                }
            }
            return null;
        }
        return $effectiveRoute;
    }




    /**
     * Ajouter une route au serveur.
     */
    public function route($route, $serverResponse) {
        if(is_callable($serverResponse)) {
            $this->routes[$route] = $serverResponse;
        } else {
            if(class_exists($serverResponse)) {
                  $this->routes[$route] = function() use ($serverResponse) {
                      (new \ReflectionClass($serverResponse))->newInstance();
                  };
            } else {
                $this->routes[$route] = function() use ($serverResponse) {
                    View::render($serverResponse);
                };
            }
        }
    }

    /**
     * Changer la route d'erreur du serveur.
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

    /**
     * Obtenir une valeur de $_GET.
     */
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

    /**
     * Obtenir une valeur de $_POST.
     */
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