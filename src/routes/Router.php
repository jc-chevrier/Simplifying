<?php

namespace simplifying\routes;

use simplifying\views\View as View;
use simplifying\Util as Util;

/**
 * Classe Router.
 *
 * @author CHEVRIER Jean-Christophe.
 */
class Router
{
    /**
     * Répertoire(s) à la racine racine,
     * Fichier à la racine.
     */
    private $ROOT_DIRECTORY, $ROOT_FILE;
    /**
     * Le chemin racine emprunté pour
     * se rendre sur une page.
     */
    private $rootPathUsed;
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
     * Cela permet les recherches de routes
     * dans le serveur.
     */
    private $tree;




    private function __construct()
    {
        //Initialisation des attributs du router.
        //Route courante nulle.
        $this->currentRoute = null;

        //Racine des routes du serveur.
        $root = explode('/', $_SERVER['SCRIPT_NAME']);
        //Fichier à la racine des routes.
        $this->ROOT_FILE = '/' . $root[count($root) - 1];
        //Repertoire(s) à la racine des routes.
        $this->ROOT_DIRECTORY = Util::removeOccurrences($this->ROOT_FILE, $_SERVER['SCRIPT_NAME']);

        //Noeud racine de l'arbre du serveur.
        $this->tree = new Node("root");

        //Route d'erreur par défaut.
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
        //Mise à jour du la route courante du serveur.
        $this->update();
        //Si null, alors la route indquée n'existe pas.
        if ($this->currentRoute == null) {
            $this->currentRoute = $this->routes['/error'];
            $this->currentRoute->beginEffective($this->currentRoute->templateRoute);
        }
        //Rendre la mise à jour de la route courante effective.
        $this->currentRoute->go();
    }

    /**
     * Mettre à jour la route courante.
     */
    private function update()
    {
        //On retire ce qui ne nous intéresse pas.
        $requestUriParts = $_SERVER['REQUEST_URI'];

        //Si il y a un/des répertoire(s) à la racine.
        if($this->ROOT_DIRECTORY != "") {
            //(1) on retire le ROOT_DIRECTORY s'il est précisé au début de l'uri.
            $requestUriParts = explode($this->ROOT_DIRECTORY, $requestUriParts);
            $countParts = count($requestUriParts);
            $requestUriParts = $requestUriParts[$countParts - 1];
            if ($countParts == 2) {
                $this->rootPathUsed = $this->ROOT_DIRECTORY;
            }
        }

        //Il y a forcément un fichier à la racine.
        //(2) on retire le ROOT_FILE s'il est précisé au début de l'uri.
        $requestUriParts = explode($this->ROOT_FILE, $requestUriParts);
        $countParts = count($requestUriParts);
        $requestUriParts = $requestUriParts[$countParts - 1];
        if($countParts == 2) {
            $this->rootPathUsed .= $this->ROOT_FILE;
        }

        //(3) On retire ce qui n"appartient pas à la route mais au $_GET,
        //ce qui est à la fin de l'uri.
        $requestUriParts = explode("?", $requestUriParts);
        $effectiveRoute = $requestUriParts[0];

        //La route / est enregistrée en tant que "slash".
        if($effectiveRoute == "/") {
            $effectiveRoute = "/slash";
        }

        //Recherche de la route modèle correspondant à cette route effective,
        //et initialisation de la route courante.
        $this->searchModelRouteInTree($effectiveRoute);
    }

    /**
     * Rediriger vers une autre route.
     */
    public function redirect($routeAlias, $routeParameters = [], $statusCode = 302)
    {
        foreach($this->routes as $templateRoute => $route) {
            //Si on a retrouvé la route à partir de l'alias.
            if($route->alias == $routeAlias) {
                $effectiveRoute = $this->prepareEffectiveRoute($route->templateRouteNodes, $routeParameters);
                $url =  $_SERVER['REQUEST_SCHEME'] . "://" . $_SERVER['SERVER_NAME'] . $this->rootPathUsed . $effectiveRoute;
                header("Location:" . $url , true, $statusCode);
                exit();
            }
        }
    }





    /**
     * Ajouter une route au serveur.
     *
     * @param $templateRoute        -> route modèle
     *
     * @param $serverResponse       -> réponse liée à la route
     *        Peut valoir :
     *        -> classe de type template
     *        -> [classe, méthode]
     *        -> code html
     *        -> callback
     *
     * @return La route créée.
     */
    public function route($templateRoute, $serverResponse)
    {
        //On transforme la réponse en callBack si nécessaire.
        if(!is_callable($serverResponse)) {
            //Si l'action associée à la route est un template.
            if (class_exists($serverResponse) && is_subclass_of($serverResponse, \simplifying\views\Template::class)) {
                $serverResponse = function () use ($serverResponse) {
                    (new \ReflectionClass($serverResponse))->newInstance();
                };
            } else {
                //Si l'action associée à la route est du code html.
                if(!class_exists($serverResponse) && is_string($serverResponse)) {
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
        list($templateRoute, $templateRouteNodes) = $this->addRouteInTree($templateRoute);
        //Ajout dans les routes.
        $this->routes[$templateRoute] = new Route($templateRoute, $templateRouteNodes, $serverResponse);

        return $this->routes[$templateRoute];
    }

    /**
     * Changer la route d'érreur du serveur.
     */
    public function routeError($serverResponseForError)
    {
        return $this->route("/error", $serverResponseForError);
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

                //Sinon, si le noeud parent n'a pas de noeuds de type ParameterNode,
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
     * Récupérer une route effective à partir d'un alias et de paramètres.
     *
     * @param $routeAlias                    L'alias de la route.
     *
     * @param array $routeParameters         Les paramètres de la route si
     *                                       la route doir avoir des paramètres.
     *
     * @return string                        La route effective.
     */
    public function getRoute($routeAlias, $routeParameters = []) {
        foreach($this->routes as $templateRoute => $route) {
            //Si on a retrouvé la route à partir de l'alias.
            if($route->alias == $routeAlias) {
                $effectiveRoute = $this->prepareEffectiveRoute($route->templateRouteNodes, $routeParameters);
                return $this->rootPathUsed . $effectiveRoute;
            }
        }
        //Sinon.
        return null;
    }

    /**
     * Préparer une route effective avec des paramètres.
     */
    private function prepareEffectiveRoute($templateRouteNodes, $routeParameters) {
        $effectiveRoute = '';

        if(count($routeParameters) != 0) {
            $keys = array_keys($routeParameters);
            $indexKey = 0;
        }

        foreach($templateRouteNodes as $index => $node) {
            $effectiveRoute .= '/';
            if($node->type == NodeType::PARAMETER_NODE) {
                if(isset($routeParameters[$node->value])) {
                    $effectiveRoute .= $routeParameters[$node->value];
                } else {
                    $effectiveRoute .= $routeParameters[$keys[$indexKey]];
                    $indexKey++;
                }
            } else {
                $effectiveRoute .= $node->value;
            }
        }

        return $effectiveRoute;
    }





    public function __get($name)
    {
        if (isset($this->$name)) {
            return $this->$name;
        }
        return false;
    }
}



