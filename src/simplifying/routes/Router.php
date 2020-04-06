<?php

namespace simplifying\routes;

use http\Exception\InvalidArgumentException;

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
            $this->currentRoute->beginEffective($this->currentRoute->templateRoute);
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

        //Recherche de la route modèle correspondant à cette route effective,
        //et initialisation de la route courante.
        $this->searchModelRouteInTree($URI);
    }




    /**
     * Ajouter une route au serveur.
     */
    public function route($templateRoute, $serverResponse)
    {
        //On transforme la réponse en callBack si nécessaire.
        if(!is_callable($serverResponse)) {
            //Si l'action associée à la route est un tamplate à envoyer au navigateur.
            if (class_exists($serverResponse)) {
                $serverResponse = function () use ($serverResponse) {
                    (new \ReflectionClass($serverResponse))->newInstance();
                };
            } else {
                //Si l'action associée à la route est du code html à envoyer au navigateur.
                if(is_string($serverResponse)) {
                    $serverResponse = function () use ($serverResponse) {
                        View::render($serverResponse);
                    };
                } else {
                    //Type de réponse pas acceptée.
                    return false;
                }
            }
        }

        //La route / est enregistrée en tant que "slash".
        if($templateRoute == "/") {
            $templateRoute = "/slash";
        }

        //Ajout dans l'arbre.
        $result = $this->addRouteInTree($templateRoute);
        $templateRoute = $result[0];
        $templateRouteNodes = $result[1];
        //Ajout dans les routes.
        $this->routes[$templateRoute] = new Route($templateRoute, $templateRouteNodes, $serverResponse);

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
        }
    }





    /**
     *  Ajouter une route modèle dans l'arbre du serveur, et récupérer
     *  la route modèle normalisée, et la route modèle en noeuds.
     *
     * @param $templateRoute    -> route modèle non normalisée
     *
     * @return array            ->  [ route modèle normalisée, route modèle en noeuds ]
     */
    private function addRouteInTree($templateRoute) {
        //On découpe la route en parties d'URI.
        $uriParts = Route::toUriParts($templateRoute);

        //Initilisation des structures à retourner.
        $templateRouteNormalized = '';
        $templateRouteNodes = [];

        $parentNode = $this->tree;
        foreach ($uriParts as $index => $uriPart) {
            $isParameter = false;
            //Si la partie de l'URI étudiée est un paramètre de route.
            if(Route::containsParameter($uriPart)) {
                $uriPart = Route::getParamaterName($uriPart);
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

            //On fait évolué les structures à retourner.
            $templateRouteNormalized .= '/' . $uriPart;
            $templateRouteNodes[] = $parentNode;
        }

        return [$templateRouteNormalized, $templateRouteNodes];
    }

    /**
     * Chercher la route modèle correspondant à une route effective
     * via l'arbre du serveur, et initialiser la route.
     */
    private function searchModelRouteInTree($effectiveRoute) {
        //On découpe la route effective en parties.
        $uriParts = Route::toUriParts($effectiveRoute);
        //On récupère l'arborescence de neoud correpondant à la route modèle.
        $nodes = $this->searchModelRouteInTreeHelper($uriParts, $this->tree);
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

            //Si la route cherchée existe.
            if(isset($this->routes[$templateRoute])) {
                //On rend la route effective.
                $this->currentRoute = $this->routes[$templateRoute];
                $this->currentRoute->beginEffective($effectiveRoute);
            //Sinon.
            } else {
                //La route cherchée n'existe pas.
                $this->currentRoute = null;
            }
        }
    }

    /**
     * Chercher une route modèle via l'arbre du serveur.
     */
    private function searchModelRouteInTreeHelper($uriParts, $parentNode, $crossedNodes = []) {
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
                    $result = $this->searchModelRouteInTreeHelper($uriPartsClone, $childParameterNode, $crossedNodesClone);
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
                return $this->searchModelRouteInTreeHelper($uriParts, $node, $crossedNodes);
            }
        }
    }





    /**
     * Récupérer une route effective à partir d'un alias.
     *
     * @param $alias                    L'alias de la route.
     *
     * @param array $parameters         Les paramètres de la route si
     *                                  la route doir avoir des paramètres.
     *
     * @return string                   La route effective.
     */
    public function getRoute($alias, $parameters = []) {
        foreach($this->routes as $templateRoute => $route) {
            //Si on a retrouvé la route à partir de l'alias.
            if($route->alias == $alias) {
                $effectiveRoute = $this->prepareEffectiveRoute($route->templateRouteNodes, $parameters);
                return "/" . $this->dir_root . $effectiveRoute;
            }
        }
        //Sinon.
        return null;
    }

    /**
     * Préparer une route effective avec des paramètres.
     */
    private function prepareEffectiveRoute($nodes, $parameters) {
        $effectiveRoute = '';
        foreach ($nodes as $index => $node) {
            $effectiveRoute .= '/';
            if($node->type == NodeType::PARAMETER_NODE) {
                if(isset($parameters[$node->value])) {
                    $effectiveRoute .= $parameters[$node->value];
                } else {
                    throw new \InvalidArgumentException("Un des paramètres de la route à préparer n'a pas été précisé !");
                }
            }else {
                $effectiveRoute .= $node->value;
            }
        }
        return $effectiveRoute;
    }





    /**
     * Accéder à ou modifier une valeur
     * de $_GET.
     */
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

    /**
     * Accéder à ou modifier une valeur
     * de $_POST.
     */
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




    public function __get($name)
    {
        if (isset($this->$name)) {
            return $this->$name;
        }
        return false;
    }
}



