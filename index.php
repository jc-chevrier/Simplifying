<?php

use \Simplifying\View as View;
use \Simplifying\Router as Router;
use \Simplifying\Template as Template;
use \Simplifying\example\SuperView as SuperView;
use \Simplifying\example\HomeView as HomeView;
use \Simplifying\example\NotesView as NotesView;

require_once('Autoloader.php');
Autoloader::register();

$router = Router::getInstance();

$router->route('/blank', SuperView::class);

$router->route('/', HomeView::class);

$router->route('/home', HomeView::class);

$router->route('/notes', function () { //use ($router) {
    new NotesView([View::div(function($i) { return "Note $i";}, 10), "La note 1 est pertinente"]);
});

$router->go();