<?php

namespace Simplifying\example;
use Simplifying\Util as Util;

class NotesView extends SuperView
{
    public function content()
    {
        return "{{body}}
                    <div>
                        Vous êtes dans les notes. 
                        <div>
                            <div>
                                <div>
                                    Notes : 
                                    <div>
                                        %%p0%%. 
                                    </div>
                                </div>
                                <div>
                                    Entre autres : 
                                    <div>
                                         %%p1%%. 
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                {{/body}}";
    }
}