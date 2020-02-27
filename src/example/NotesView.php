<?php

namespace Simplifying\example;

class NotesView extends SuperView
{
    public function content()
    {
        return "{{body}}
                    <div>
                        Vous êtes dans les notes. Notes : <br><br>" . $this->parameters[0] . "
                    </div>
                {{/body}}";
    }
}