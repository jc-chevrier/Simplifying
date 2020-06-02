<?php

require_once('Autoloader.php');
Autoloader::register();

use \simplifying\routes\Router as Router;
use \simplifying\views\Template as Template;

$router = Router::getInstance();

$router->route('/test', function() {
    Template::render('HomeView');
});

$router->go();