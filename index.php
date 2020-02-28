<?php

use \simplifying\View as View;
use \simplifying\Router as Router;
use \example\SuperView as SuperView;
use \example\HomeView as HomeView;
use \example\NotesView as NotesView;
use \example\ContactView as ContactView;

require_once('Autoloader.php');
Autoloader::register();

$router = Router::getInstance();

$router->route('/blank', SuperView::class);

$router->route('/', HomeView::class);

$router->route('/home', HomeView::class);

$router->route('/notes', function () { //use ($router) {
    new NotesView([View::div(function($i) { return "Note $i";}, 10), "La note 1 est pertinente"]);
});

$router->route('/contact', ContactView::class);

$router->routeError(function() {
    class ErrorView extends SuperView {
        public function content()
        {
            return "{{body}}Cette page n'existe pas sur le serveur.{{/body}}";
        }
    }
    new ErrorView();
});

$router->go();