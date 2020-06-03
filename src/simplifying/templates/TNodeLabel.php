<?php

namespace simplifying\templates;

/**
 * Classe TNodeLabel.
 *
 * T <=> Template.
 *
 * @author CHEVRIER Jean-Christophe.
 */
class TNodeLabel
{
    const PARENT = 'parent';

    const ABSTRACT_BLOCK = 'ablock';
    const BLOCK = 'block';
    const END_BLOCK = '/block';

    const LOOP = 'for';
    const END_LOOP = '/for';

    const CONDITION = 'if';
    const END_CONDITION = '/if';

    const VALUE = 'val';

    const ROUTE = 'route';

    const IGNORED = 'ignored';
}