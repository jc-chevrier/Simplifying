<?php

namespace example;

class ContactView extends SuperView
{
    public function content()
    {
        return "{{body}}
                        <br>
                        <div>
                            Some links : 
                        </div>
                        <ul>
                            <li>
                                %%values:link-gf%%
                            </li>
                            <li>
                                %%values:link-gc%%
                            </li>
                            <li>
                                %%values:link-ec%%
                            </li>    
                        </ul>
                {{/body}}";
    }
}