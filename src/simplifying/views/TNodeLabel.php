<?php

namespace simplifying\views;

/**
 * Classe TNodeLabel.
 *
 * TNode <=> Template Node, noeud de nature <<...>>.
 * regExp <=> regular expression.
 *
 * @author CHEVRIER Jean-Christophe.
 */
class TNodeLabel
{
    const PARENT = 'parent';

    const BLOCK = 'block';
    const END_BLOCK = '/block';

    const LOOP = 'for';
    const END_LOOP = '/for';

    const CONDITION = 'if';
    const END_CONDITION = '/if';

    const VAL = 'val';

    const ROUTE = 'route';
}