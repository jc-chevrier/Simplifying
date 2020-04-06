<?php

namespace example;

class HomeView extends SuperView
{
    public function content()
    {
        return "{{body}}<div class=blue>Vous êtes dans le home.</div>{{/body}}";
    }
}