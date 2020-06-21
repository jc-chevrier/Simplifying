<?php

namespace simplifying\routes;

use simplifying\Util as Util;

/**
 * Classe Router.
 *
 * @author CHEVRIER Jean-Christophe
 * @package simplifying\routes
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



    /**
     * Router constructor.
     */
    private function __construct() {
        //Initialisation des attributs du router.
        //Route courante nulle.
        $this->currentRoute = null;
        //Racine des routes du serveur.
        $script_name = $_SERVER['SCRIPT_NAME'];
        $root = explode('/', $script_name);
        //Fichier à la racine des routes.
        $this->ROOT_FILE = '/' . $root[count($root) - 1];
        //Repertoire(s) à la racine des routes.
        $this->ROOT_DIRECTORY = Util::removeOccurrences($this->ROOT_FILE, $script_name);
        //Noeud racine de l'arbre du serveur.
        $this->tree = new UriNode("root");
        //Route d'erreur par défaut.
        $this->route('/error', function(){return"<html><body><div>Page inexistante sur le serveur.</div></body></html>";});
    }

    /**
     * Obtenir le singleton router de l'extérieur.
     *
     * @return Router
     */
    public static function getInstance() : Router {
        if (Router::$router == null) {
            Router::$router = new Router();
        }
        return Router::$router;
    }



    /**
     * Effectuer l'action correspondante à la route courante.
     */
    public function run() : void {
        //Mise à jour du la route courante du serveur.
        $this->update();
        //Si null, alors la route indquée n'existe pas.
        if ($this->currentRoute == null) {
            $this->currentRoute = $this->routes['/error'];
            $this->currentRoute->beginEffective($this->currentRoute->templateRoute);
        }
        //Rendre la mise à jour de la route courante effective.
        $this->currentRoute->run();
    }

    /**
     * Mettre à jour la route courante.
     */
    private function update() : void {
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
     *
     * @param string $routeAlias
     * @param array $routeParameters
     * @param int $statusCode
     */
    public function redirect(string $routeAlias, array $routeParameters = [], int $statusCode = 302) : void {
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
     * @param string $templateRoute -> route modèle
     * @param callable $action
     * @return Route
     */
    public function route(string $templateRoute, callable $action) : Route {
        //La route / est enregistrée en tant que "slash".
        if($templateRoute == "/") {
            $templateRoute = "/slash";
        }
        //Ajout dans l'arbre.
        list($templateRouteNormalized, $templateRouteNodes) = $this->addRouteInTree($templateRoute);
        //Ajout dans les routes.
        $route = new Route($templateRouteNormalized, $templateRouteNodes, $action);
        $this->routes[$templateRouteNormalized] = $route;
        return $route;
    }

    /**
     * Changer la route d'érreur du serveur.
     *
     * @param string $serverResponseForError
     * @return Route
     */
    public function routeError(string $serverResponseForError) : Route {
        return $this->route("/error", $serverResponseForError);
    }



    /**
     *  Ajouter une route modèle dans l'arbre du serveur, et récupérer
     *  la route modèle normalisée, et la route modèle en noeuds.
     *
     * @param string $templateRoute    -> route modèle non normalisée
     * @return array                   ->  [ route modèle normalisée, route modèle en noeuds ]
     */
    private function addRouteInTree(string $templateRoute) : array {
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
                $uriPart = Route::getParameterName($uriPart);
                $isParameter = true;
            }

            //On cherche le noeud pouvant déjà existé en tant
            //que noeud enfant du noeud parent $parentNode.
            $node = $parentNode->searchNodeInChildNodes($uriPart);

            //Si aucun noeud enfant existe.
            if($node == null) {
                //On en crée un.
                $childNode = $isParameter ? new ParameterUriNode($uriPart) : new UriNode($uriPart);
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
     *
     * @param string $effectiveRoute
     */
    private function searchModelRouteInTree(string $effectiveRoute) : void {
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
            //On récupère la route modèle.
            $templateRoute = array_reduce($nodes, function($acc, $node){$acc .= '/' . $node->value; return $acc;}, '');
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
     *
     * @param array $uriParts
     * @param UriNode $parentNode
     * @param array $crossedNodes
     * @return array|null
     */
    private function searchModelRouteInTreeHelper(array $uriParts, UriNode $parentNode, array $crossedNodes = []) : array {
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
     * @param string $routeAlias                    L'alias de la route.
     * @param array $routeParameters                Les paramètres de la route si la route doir avoir des paramètres.
     * @return string                               La route effective.
     */
    public function getRoute(string $routeAlias, array $routeParameters = []) : string {
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
     *
     * @param array $templateRouteNodes
     * @param array $routeParameters
     * @return string
     */
    private function prepareEffectiveRoute(array $templateRouteNodes, array $routeParameters) : string {
        $effectiveRoute = '';

        if(count($routeParameters) != 0) {
            $keys = array_keys($routeParameters);
            $indexKey = 0;
        }

        foreach($templateRouteNodes as $index => $node) {
            $effectiveRoute .= '/';
            if($node->type == UriNodeType::PARAMETER_URI_NODE) {
                if(isset($routeParameters[$node->value])) {
                    $effectiveRoute .= $routeParameters[$node->value];
                } else {
                    $effectiveRoute .= $routeParameters[$keys[$indexKey]];
                    $indexKey++;
                }
            }else {
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



