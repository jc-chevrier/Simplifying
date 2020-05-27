<?php

namespace example;

class NotesView extends SuperView
{
    public function content()
    {
        $router = \simplifying\routes\Router::getInstance();
        $this->value('link-note1', $router->getRoute('NOTE1', [ 'id' => 4 ]));
        $this->value('link-note2', $router->getRoute('NOTE2', [ 'id' => 4 ]));
        $this->value('link-note3', $router->getRoute('NOTE3', [ 4 ]));

        return "{{body}}
                    <div>
                        Vous êtes dans les notes. 
                        <div>
                            <div>
                                <br>
                                <div>
                                    Notes : 
                                    <div>
                                        %%params:notes%%. 
                                    </div>
                                </div>
                                <br>
                                <div>
                                    Entre autres : 
                                    <div>
                                         %%params:note1%%. 
                                    </div>
                                </div>
                                <div>
                                    <a href=%%values:link-note1%%>Note 4.</a>
                                    <br>
                                    <a href=%%values:link-note2%%>Note 4 : détails.</a>
                                    <br>
                                    <a href=%%values:link-note3%%>Note 4 : divers.</a>
                                    <br>
                                    <a href=%%routes:NOTE4:4:3%%>Note 4. Détail 3.</a>
                                </div>
                            </div>
                        </div>
                    </div>
                {{/body}}";
    }
}