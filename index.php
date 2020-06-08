<?php

require_once('Autoloader.php');
Autoloader::register();

use \simplifying\routes\Router as Router;
use \simplifying\templates\Template as Template;

$router = Router::getInstance();

$router->route('/test/{id}', function() {
    $set = [];
    $set2_1 = [ "1", "1", "1" ];
    $set2_2 = [ "2", "2", "2" ];
    $set2_3 = [ "3", "3", "3" ];
    $set[] = $set2_1;
    $set[] = $set2_2;
    $set[] = $set2_3;
    $isConnected = false;
    Template::render('HomeView', [ "set" => $set, "isConnected" => $isConnected ]);
});

$router->route('/test2/{id}/{id2}', function($id, $id2) {
    echo $id . "---" . $id2;
})->alias('test2');

$router->go();