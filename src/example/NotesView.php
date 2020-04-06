<?php

namespace example;

class NotesView extends SuperView
{
    public function content()
    {
        $router = \simplifying\routes\Router::getInstance();
        $this->value('link-note1', $router->getRoute('NOTE1', [ 'id' => 4 ]));
        $this->value('link-note2', $router->getRoute('NOTE2', [ 'id' => 4 ]));
        $this->value('link-note3', $router->getRoute('NOTE3', [ 'id' => 4 ]));
        $this->value('link-note4', $router->getRoute('NOTE4', ['idNote' => 4 ,'idDetail' => 3]));

        return "{{body}}
                    <div>
                        Vous êtes dans les notes. 
                        <div>
                            <div>
                                <br>
                                <div>
                                    Notes : 
                                    <div>
                                        %%notes%%. 
                                    </div>
                                </div>
                                <br>
                                <div>
                                    Entre autres : 
                                    <div>
                                         %%note1%%. 
                                    </div>
                                </div>
                                <div>
                                    <a href=%%link-note1%%>Note 4.</a>
                                    <br>
                                    <a href=%%link-note2%%>Note 4 : détails.</a>
                                    <br>
                                    <a href=%%link-note3%%>Note 4 : divers.</a>
                                    <br>
                                    <a href=%%link-note4%%>Note 4. Détail 3.</a>
                                </div>
                            </div>
                        </div>
                    </div>
                {{/body}}";
    }
}