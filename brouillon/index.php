<?php

use \Simplifying\View as View;
use \Simplifying\Router as Router;
use \Simplifying\Template as Template;
use \Simplifying\example\ViewHome as homeView;

require_once('Autoloader.php');
Autoloader::register();



$router = Router::getInstance();



$router->route('/', function () {
    View::render('
     <html>
           <body>
                 <div>
                       HOME
                 </div>   
          </body>
     </html>
    ');
});



$router->route('/home', function () {
    View::render('
     <html>
           <body>
                 <div>
                       HOME
                 </div>   
          </body>
     </html>
    ');
});



$router->route('/game', function () {
    $cases = View::div(function ($i) {
        return $i;
    }, 9, "case");
    $board = View::div($cases, 1, "case-container");

    View::render("
     <html>
            <head>
                   <style>
                        .case-container {
                            display: flex;
                            flex-direction: row;
                            flex-wrap: wrap;
                            width: 220px;
                            height: 220px;
                            padding: 0px;
                        }
                        
                        .case {
                            width: 60px;
                            height: 60px;
                            border: 1px solid rgb(70,130,180);
                            background-color: rgb(135,206,235);
                            color: white;
                            margin: 5px;
                            text-align: center;
                            padding: auto;
                        }
                    </style>
            </head>
            <body>
                   $board
            </body>
     </html>
    ");
});



$router->route('/test1', function () {
    class Template1 extends Template
    {
        public function content()
        {
            return "<div>
                        <div>
                                Template mère.
                        </div>
                        [[corps]]
                        [[corps2]]
                        [[corps3]]
                        [[corps4]]
                        [[corps5]]
                    <div>";
        }
    }


    new Template1();
});



$router->route('/test2', function () {
    class Template1 extends Template
    {
        public function content()
        {
            return "<div>
                        <div>
                                Template mère.
                        </div>
                        [[corps]]
                        [[corps2]]
                        [[corps3]]
                        [[corps4]]
                        [[corps5]]
                    <div>";
        }
    }

    class Template2 extends Template1
    {
        public function content()
        {
            return "{{corps}}
                           <div>Template fille</div>
                           <div>Remplacé 1</div>
                     {{/corps}}
                     {{corps2}}
                           <div>Template fille</div>
                           <div>Remplacé2</div>
                     {{/corps2}}";
        }
    }

    new Template2();
});



$router->route('/test3', function () {
    class Template1 extends Template
    {
        public function content()
        {
            return "<div>
                        <div>
                                Template mère.
                        </div>
                        [[corps]]
                        [[corps2]]
                        [[corps3]]
                    <div>";
        }
    }

    class Template2 extends Template1
    {
        public function content()
        {
            return "{{corps}}
                           <div>Template fille.</div>
                           [[corps-2-1]]
                           [[corps-2-2]]
                    {{/corps}}";
        }
    }

    class Template3 extends Template2
    {
        public function content()
        {
            return "{{corps-2-1}}<div>Template petite-fille.</div>{{/corps-2-1}}";
        }
    }

    new Template3();
});



$router->route('/game2', function () {
    class Board extends Template
    {
        public function content()
        {
            $cases = View::div(function ($i) {return $i;}, 9, "case");
            $board = View::div($cases, 1, "case-container");
            return $board;
        }
    }

    new Board();
});



$router->go();