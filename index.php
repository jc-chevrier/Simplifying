<?php

require_once('Autoloader.php');
Autoloader::register();

use \simplifying\routes\Router as Router;
use \simplifying\templates\Template as Template;

$router = Router::getInstance();

$router->route('/test', function() {
    Template::render('HomeView');
});

$router->route('/test2/{id}/{id2}', function($id, $id2) {
    echo $id . "---" . $id2;
})->alias('test2');

$router->go();