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
    const BLOCK_LABELS = [ TNodeLabel::BLOCK, TNodeLabel::END_BLOCK ];

    const FOR = 'for';
    const END_FOR = '/for';
    const FOR_LABELS = [ TNodeLabel::FOR, TNodeLabel::END_FOR ];

    const IF = 'if';
    const THEN = 'then';
    const ELSE = 'else';
    const END_IF = '/if';
    const IF_LABELS = [ TNodeLabel::IF, TNodeLabel::THEN, TNodeLabel::ELSE, TNodeLabel::END_IF ];
    private const IF_LABELS_1 = [ TNodeLabel::THEN, TNodeLabel::END_IF ];
    private const IF_LABELS_2 = [ TNodeLabel::ELSE, TNodeLabel::END_IF ];

    const IF_NOT = 'ifnot';
    const END_IF_NOT = '/ifnot';
    const IF_NOT_LABELS = [ TNodeLabel::IF_NOT,  TNodeLabel::END_IF_NOT, TNodeLabel::THEN ];

    const TERNARY_EXPRESSION = 'ternary';
    const TERNARY_EXPRESSION_LABELS = [ TNodeLabel::TERNARY_EXPRESSION, TNodeLabel::THEN, TNodeLabel::ELSE ];

    const VALUE = 'val';

    const ROUTE = 'route';

    const IGNORED = 'ignored';

    const ROOT = 'root';



    /**
     * @param string $TNodeLabel1
     * @param string $TNodeLabel2
     * @return bool
     */
    public static function TNodeLabelsAreComplementary(string $TNodeLabel1, string $TNodeLabel2) : bool {
        return TNodeLabel::_TNodeLabelsAreComplementary($TNodeLabel1, $TNodeLabel2, TNodeLabel::FOR_LABELS) ||
               TNodeLabel::_TNodeLabelsAreComplementary($TNodeLabel1, $TNodeLabel2, TNodeLabel::IF_LABELS_1) ||
               TNodeLabel::_TNodeLabelsAreComplementary($TNodeLabel1, $TNodeLabel2, TNodeLabel::IF_LABELS_2) ||
               TNodeLabel::_TNodeLabelsAreComplementary($TNodeLabel1, $TNodeLabel2, TNodeLabel::IF_NOT_LABELS) ||
               TNodeLabel::_TNodeLabelsAreComplementary($TNodeLabel1, $TNodeLabel2, TNodeLabel::BLOCK_LABELS);
    }

    /**
     * @param string $TNodeLabel1
     * @param string $TNodeLabel2
     * @param array $TNodeLabels
     * @return bool
     */
    private static function _TNodeLabelsAreComplementary(string $TNodeLabel1, string $TNodeLabel2, array $TNodeLabels) : bool {
        return $TNodeLabel1 != $TNodeLabel2 &&
               TNodeLabel::TNodeLabelBelongsTo($TNodeLabel1, $TNodeLabels) &&
               TNodeLabel::TNodeLabelBelongsTo($TNodeLabel2, $TNodeLabels);
    }

    /**
     * @param string $TNodeLabel
     * @param array $TNodeLabels
     * @return bool
     */
    private static function TNodeLabelBelongsTo(string $TNodeLabel, array $TNodeLabels) : bool {
        return array_search($TNodeLabel, $TNodeLabels) !== false;
    }



    /**
     * @param string $label
     * @return bool
     */
    public static function isTNodeLabel(string $label) : bool {
        switch ($label) {
            case TNodeLabel::VALUE :
            case TNodeLabel::ROUTE :
            case TNodeLabel::PARENT :
            case TNodeLabel::ABSTRACT_BLOCK :
            case TNodeLabel::BLOCK :
            case TNodeLabel::END_BLOCK :
            case TNodeLabel::IF :
            case TNodeLabel::THEN :
            case TNodeLabel::ELSE :
            case TNodeLabel::END_IF :
            case TNodeLabel::IF_NOT :
            case TNodeLabel::END_IF_NOT :
            case TNodeLabel::TERNARY_EXPRESSION :
            case TNodeLabel::FOR :
            case TNodeLabel::END_FOR :
            case TNodeLabel::IGNORED :
            case TNodeLabel::ROOT :
                return true;
            default:
                return false;
        }
    }
}