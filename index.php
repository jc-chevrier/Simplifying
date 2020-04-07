<?php

use \simplifying\views\View as View;
use \simplifying\routes\Router as Router;
use \example\SuperView as SuperView;
use \example\HomeView as HomeView;
use \example\NotesView as NotesView;

require_once('Autoloader.php');
Autoloader::register();

$router = Router::getInstance();




$router->route('/blank', SuperView::class)->alias('BLANK');

$router->route('/', HomeView::class);

$router->route('/home', HomeView::class)->alias('HOME');




$router->route('/notes', function () {
    new NotesView(["notes" => View::div(function($i) { return "Note $i";}, 10), "note1" => "La note 1 est pertinente"]);
})->alias('NOTES');

$router->route('/notes/note/{id}', function () {
    class NoteView1 extends SuperView {
        public function content() {
            return "{{body}} 
                            Note %%route:id%%. 
                            <br>
                            <a href=%%routes:NOTE4:1:3%%>
                                Url fixe. Note 1. Détail 3.
                            </a>  
                            <br>       
                            <a href=%%routes:NOTE4:%%route:id%%:3%%>
                                Url dépendant du paramètre id. Note %%route:id%%. Détail 3.
                            </a>        
                    {{/body}}";
        }
    }
    new NoteView1();
})->alias("NOTE1");

$router->route('/notes/note/{id}/details', function () {
    class NoteView2 extends SuperView {
        public function content() {
            return "{{body}} Note %%route:id%% : détails. {{/body}}";
        }
    }
    new NoteView2();
})->alias("NOTE2");

$router->route('/message/{message}', function (){
    class MessageView extends SuperView {
        public function content() {
            return "{{body}} Message : %%route:message%%. {{/body}}";
        }
    }
    new MessageView();
})->alias("MESSAGE");

$router->route('/notes/note/{id}/divers', function () {
    $pileFace = rand(1, 2);
    if($pileFace == 1) {
        Router::getInstance()->redirect("MESSAGE", ["PileFace vaut $pileFace" ]);
    } else {
        class NoteView3 extends SuperView {
            public function content() {
                return "{{body}} Note %%route:id%% : divers, PileFace vaut %%params:pileFace%%. {{/body}}";
            }
        }
        new NoteView3(["pileFace" => $pileFace]);
    }
})->alias("NOTE3");

$router->route('/notes/note/{idNote}/details/detail/{idDetail}', function () {
    class NoteView4 extends SuperView {
        public function content() {
            return "{{body}} Note %%route:idNote%%. Détail %%route:idDetail%%. {{/body}}";
        }
    }
    new NoteView4();
})->alias("NOTE4");




$router->route('/arbre', function () use ($router) {
    class TreeView extends SuperView {
        public function content() {
            return "{{body}} <div class=blue>%%params:tree%%</div> {{/body}}";
        }
    }
    new TreeView(["tree" => $router->tree->toString()]);
})->alias('TREE');

$router->route('/routes', function () {
    class RoutesView extends SuperView {
        public function content() {
            $routes = Router::getInstance()->routes;

            $content = "{{body}}";
            foreach($routes as $index => $route) {
                $content .= "<div>
                                    <div>
                                          Route <b>modèle</b> :
                                          <span class=blue> 
                                                $route->templateRoute
                                          </span>
                                    </div>
                                    <div>
                                         Route <b>alias</b> : 
                                          <span class=green> 
                                                " . ($route->alias ? $route->alias  : 'pas d\'alias')  . "
                                          </span>

                                    </div>
                                    <div>
                                           Route <b>modèle en noeuds</b> : 
                                           <span class=red>";

                foreach($route->templateRouteNodes as $index2 => $node) {
                        $content .= $index2 ? ">>>>" : "";
                        $content .= "[$node->type : $node->value]";
                }

                $content .= "            </span>     
                                    </div>
                                    <br>
                                    <br>
                                    <br>
                                    <hr>
                                </div>";
            }
            $content .= "{{/body}}";

            return $content;
        }
    }
    new RoutesView();
})->alias('ROUTES');




$router->routeError(function() {
    class ErrorView extends SuperView {
        public function content() {
            return "{{body}} 
                        <div>
                            Cette page est introuvable sur le serveur. 
                        </div>
                        <a href=%%routes:HOME%% class=green>
                            Se rendre vers le home ?
                        </a>
                     {{/body}}";
        }
    }
    new ErrorView();
});




$router->go();