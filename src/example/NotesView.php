<?php

namespace example;

class NotesView extends SuperView
{
    public function content()
    {
        $router = \simplifying\routes\Router::getInstance();
        $this->value('link-note4',$router->getRoute('NOTE4', ['idNote' => 4, 'idDetail' => 3]));

        return "{{body}}
                    <div>
                        Vous êtes dans les notes. 
                        <div>
                            <div>
                                <br>
                                <div>
                                    Notes : 
                                    <div>
                                        %%0%%. 
                                    </div>
                                </div>
                                <br>
                                <div>
                                    Entre autres : 
                                    <div>
                                         %%1%%. 
                                    </div>
                                </div>
                                <div>
                                    <a href=%%link-note4%%>Note 4. Détail 3.</a>
                                </div>
                            </div>
                        </div>
                    </div>
                {{/body}}";
    }
}