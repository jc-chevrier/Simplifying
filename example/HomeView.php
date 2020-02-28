<?php

namespace example;

class HomeView extends SuperView
{
    public function content()
    {
        return "{{body}}<div>Vous êtes dans le home.</div>{{/body}}";
    }
}