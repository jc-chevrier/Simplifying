<?php

namespace simplifying\routes;

/**
 * Classe Router.
 */
class Router
{
    /**
     * Racine du serveur.
     */
    private $dir_root, $file_root;
    /**
     * Route courante.
     */
    private $currentRoute;
    /**
     * Routes du serveur.
     */
    private $routes;
    /**
     * Un singleton router.
     *
     * Permmet un acces de l'extérieur.
     */
    private static $router;
    /**
     * Arboresence du site stockée
     * via un arbre n-aires.
     *
     * Cela permet les recherches de Route sur le site.
     */
    private $tree;




    private function __construct()
    {
        $this->currentRoute = null;

        $root = explode('/', $_SERVER['SCRIPT_NAME']);
        $this->dir_root = $root[1];
        $this->file_root = $root[2];

        $this->tree = new Node("root");

        $this->route('/error', '             
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
    public static function getInstance()
    {
        if (Router::$router == null) {
            Router::$router = new Router();
        }
        return Router::$router;
    }




    /**
     * Effectuer l'action correspondante à la route courante.
     */
    public function go()
    {
        $this->update();
        if ($this->currentRoute == null) {
            $this->currentRoute = $this->routes['/error'];
        }
        $this->currentRoute->go();
    }

    /**
     * Mettre à jour la route courante.
     */
    private function update()
    {
        //On retire ce qui n"appartient pas à la route au début de l'URI.
        $URI = explode($this->dir_root, $_SERVER['REQUEST_URI'])[1];
        $URI = explode($this->file_root, $URI);
        $URI = $URI[count($URI) - 1];

        //On retire ce qui n"appartient pas à la route à la fin de l'URI.
        $URI = explode("?", $URI);
        $URI = $URI[0];

        //La route / est enregistrée en tant que "slash".
        if($URI == "/") {
            $URI = "/slash";
        }

        $this->searchRouteInTree($URI);
    }




    /**
     * Ajouter une route au serveur.
     */
    public function route($templateRoute, $serverResponse)
    {
        //La route / est enregistrée en tant que "slash".
        if($templateRoute == "/") {
            $templateRoute = "/slash";
        }

        //Ajout dans l'arbre.
        $this->addRouteInTree($templateRoute);

        //Ajout dans les routes.
        //Si l'action associée à la route est une fonction.
        if (is_callable($serverResponse)) {
            $this->routes[$templateRoute] = new Route($templateRoute, $serverResponse);
        } else {

            //Si l'action associée à la route est un tamplate à envoyer au navigateur.
            if (class_exists($serverResponse)) {
                $callbackServerResponse = function () use ($serverResponse) {
                    (new \ReflectionClass($serverResponse))->newInstance();
                };
                $this->routes[$templateRoute] = new Route($templateRoute, $callbackServerResponse);

            //Si l'action associée à la route est du code html à envoyer au navigateur.
            } else {
                $callbackServerResponse = function () use ($serverResponse) {
                    View::render($serverResponse);
                };
                $this->routes[$templateRoute] = new Route($templateRoute, $callbackServerResponse);
            }
        }

        return $this->routes[$templateRoute];
    }

    /**
     * Changer la route d'érreur du serveur.
     */
    public function routeError($serverResponseForError)
    {
        $this->route("/error", $serverResponseForError);
    }

    /**
     * Rediriger vers une autre route.
     */
    public function redirect($templateRoute)
    {
        if (isset($this->routes[$templateRoute])) {
            $this->currentRoute = $this->routes[$templateRoute];
            $this->currentRoute->go();
        }
    }




    /**
     *  Ajouter une route dans l'arbre du serveur.
     */
    private function addRouteInTree($templateRoute) {
        //On découpe la route en parties d'URI.
        $uriParts = Route::toUriParts($templateRoute);

        $parentNode = $this->tree;
        foreach ($uriParts as $index => $uriPart) {
            $isParameter = false;
            //Si la partie de l'URI étudiée est un paramètre de route.
            if(Route::containsParameter($uriPart)) {
                $isParameter = true;
            }

            //On cherche le noeud pouvant déjà existé en tant
            //que noeud enfant du noeud parent $parentNode.
            $node = $parentNode->searchNodeInChildNodes($uriPart);

            //Si aucun noeud enfant existe.
            if($node == null) {
                //On en crée un.
                $childNode = $isParameter ? new ParameterNode($uriPart) : new Node($uriPart);
                $parentNode->addChild($childNode);
                $parentNode = $childNode;
            //Sinon.
            } else {
                $parentNode = $node;
            }
        }
    }

    /**
     * Chercher la route correspondant à une route effective
     * via l'arbre du serveur, et initialiser la route.
     */
    private function searchRouteInTree($effectiveRoute) {
        //On découpe la route effective en parties.
        $uriParts = Route::toUriParts($effectiveRoute);
        //On récupère l'arborescence de neoud correpondant à la route modèle.
        $nodes = $this->searchRouteInTreeHelper($uriParts, $this->tree);
        //Si pas de noeuds trouvés.
        if($nodes == null) {
            //La route cherchée n'existe pas.
            $this->currentRoute = null;
        //Sinon.
        } else {
            $templateRoute = '';
            //On récupère la route modèle.
            foreach($nodes as $index => $node) {
                $templateRoute .= '/' . $node->value;
            }
            //On rend la route effective.
            $this->currentRoute = $this->routes[$templateRoute];
            $this->currentRoute->beginEffective($effectiveRoute, $nodes);
        }
    }

    private function searchRouteInTreeHelper($uriParts, $parentNode, $crossedNodes = []) {
        //Cas trivial.
        if(count($uriParts) == 0) {
            return $crossedNodes;

        //Cas récursif.
        } else {
            //Récupération de la partie de l'uri à gérer avec l'appel.
            $uriPart = array_shift($uriParts);

            //On cherche le noeud enfant du neoud parent qui correspond
            //à la partie d'uri.
            $node = $parentNode->searchNodeInChildNodes($uriPart);

            //Si on ne trouve pas de neoud, c'est peut-être que
            //la partie d'uri est un paramètre.
            if($node == null) {
                //On cherche les noeuds enfants de type ParameterNode du noeud parent.
                $childParameterNodes = $parentNode->searchChildParameterNodes();

                //Si le noeud parent a des noeuds de type ParameterNode, on
                //cherche parmis ces noeuds.
                foreach ($childParameterNodes as $index => $childParameterNode) {
                    $uriPartsClone = (new \ArrayObject($uriParts))->getArrayCopy();
                    $crossedNodesClone = (new \ArrayObject($crossedNodes))->getArrayCopy();
                    $crossedNodesClone[] = $childParameterNode;
                    $result = $this->searchRouteInTreeHelper($uriPartsClone, $childParameterNode, $crossedNodesClone);
                    if($result != null) {
                        return $result;
                    }
                }

                //Sinon, si le noeud parent n'a pas de neouds de type ParameterNode,
                //alors on abandonne cette branche d'appel.
                return null;
            //Sinon, on a trouvé un noeud.
            } else {
                $crossedNodes[] = $node;
                return $this->searchRouteInTreeHelper($uriParts, $node, $crossedNodes);
            }
        }
    }




    public function __get($name)
    {
        if (isset($this->$name)) {
            return $this->$name;
        }
        return false;
    }

    public function get($name, $value = null)
    {
        if ($value != null) {
            $_GET[$name] = $value;
        } else {
            if (isset($_GET[$name])) {
                return $_GET[$name];
            }
            return false;
        }
    }

    public function post($name, $value = null)
    {
        if ($value != null) {
            $_POST[$name] = $value;
        } else {
            if (isset($_POST[$name])) {
                return $_POST[$name];
            }
            return false;
        }
    }
}



