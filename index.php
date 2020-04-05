<?php

use \simplifying\views\View as View;
use \simplifying\routes\Router as Router;
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

$router->route('/notes/note/{id}', function () { //use ($router) {
    class NoteView1 extends SuperView {
        public function content() {
            return "{{body}} Node %%id%%.{{/body}}";
        }
    }
    new NoteView1();
});

$router->route('/notes/note/{id}/details', function () {
    class NoteView2 extends SuperView {
        public function content() {
            return "{{body}} Node %%id%% : détails.{{/body}}";
        }
    }
    new NoteView2();
});

$router->route('/notes/note/{id}/rien', function () {
    class NoteView3 extends SuperView {
        public function content() {
            return "{{body}} Node %%id%% : rien.{{/body}}";
        }
    }
    new NoteView3();
});

$router->route('/notes/note/{idNote}/details/detail/{idDetail}', function () {
    class NoteView4 extends SuperView {
        public function content() {
            return "{{body}} Node %%idNote%%. Détail %%idDetail%%.{{/body}}";
        }
    }
    new NoteView4();
});

$router->route('/arbre', function () use ($router) {
    class TreeView extends SuperView {
        public function content() {
            return "{{body}} %%ptree%% {{/body}}";
        }
    }
    new TreeView(["tree" => $router->tree->toString()]);
});

$router->route('/contact', ContactView::class);

$router->routeError(function() {
    class ErrorView extends SuperView {
        public function content() {
            return "{{body}}Cette page n'existe pas sur le serveur.{{/body}}";
        }
    }
    new ErrorView();
});

$router->go();