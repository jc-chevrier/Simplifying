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
                                %%link-gf%%
                            </li>
                            <li>
                                %%link-gc%%
                            </li>
                            <li>
                                %%link-ec%%
                            </li>    
                        </ul>
                {{/body}}";
    }
}