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

$router->route('/parent', function () {
    new SuperView();
});

$router->route('/', function () {
    new HomeView();
});

$router->route('/home', function () {
   new HomeView();
});

$router->route('/notes', function () {
    new NotesView([View::div(function($i) { return "Note $i";}, 10)]);
});

$router->go();