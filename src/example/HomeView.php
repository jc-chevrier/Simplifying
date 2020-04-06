<?php

namespace example;

class HomeView extends SuperView
{
    public function content()
    {
        return "{{css}} ff {{/css}}{{body}}<div>Vous êtes dans le home.</div>{{/body}}";
    }
}