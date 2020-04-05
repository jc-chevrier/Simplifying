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
                                        %%p0%%. 
                                        %%ff%%
                                    </div>
                                </div>
                                <br>
                                <div>
                                    Entre autres : 
                                    <div>
                                         %%p1%%. 
                                    </div>
                                </div>
                                <div>
                                        <a href=%%link-note4%%>%%link-note4%%</a>
                                </div>
                            </div>
                        </div>
                    </div>
                {{/body}}";
    }
}