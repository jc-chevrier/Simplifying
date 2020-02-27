<?php

namespace Simplifying\example;

class SuperView extends \Simplifying\Template
{
    public function content()
    {
        return "<html lang='french'>
                        <head>
                                [[headers]]
                                [[scripts]]
                                [[css]]
                                <style>
                                    .row, .rows, .rows, .columns {
                                        display: flex;
                                    }
                                    .row, .rows {
                                        display: flex;
                                        flex-direction: row;
                                    }
                                    .rows, .columns {
                                        flex-wrap: wrap;
                                    }
                                    .column, .columns {
                                        flex-direction: column;
                                    }
                                    .menus {
                                        padding: auto;
                                        width: 100%;
                                        b
                                    }
                                    .menus > * {
                                        margin-left: 20px;
                                        margin-bottom: 20px;
                                        background-color: cornflowerblue;
                                        color: white;
                                        border: 1px solid blue;
                                        text-decoration: none;
                                        text-align: center;
                                        padding: 10px;
                                        font-size: 15px;
                                        font-style: italic;
                                        font-weight: bold;
                                    }
                                </style>
                        </head>
                        <body>
                                <div class='row menus'>
                                      <a href=home>home</a>
                                      <a href=notes>notes</a>
                                      <a href=other>divers</a>
                                      <a href=contact>contact</a>
                                </div>
                                [[body]]
                        </body>
               </html>";
    }
}