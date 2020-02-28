<?php

namespace Simplifying\example;

use Simplifying\Template;

class NotesView extends SuperView
{
    public function content()
    {
        NotesView::$router->get('notes', "1-10");
        return "{{body}}
                    <div>
                        Vous êtes dans les notes %%notes%%. Notes : <br><br>" . $this->parameters[0] . "
                    </div>
                {{/body}}";
    }
}