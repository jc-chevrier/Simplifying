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

    const LOOP = 'for';
    const END_LOOP = '/for';
    const LOOP_LABELS = [ TNodeLabel::LOOP, TNodeLabel::END_LOOP ];

    const CONDITION = 'if';
    const CONDITION_ELSE = 'else';
    const END_CONDITION = '/if';
    const CONDITION_LABELS = [ TNodeLabel::CONDITION, TNodeLabel::CONDITION_ELSE, TNodeLabel::END_CONDITION ];

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
        return TNodeLabel::_TNodeLabelsAreComplementary($TNodeLabel1, $TNodeLabel2, TNodeLabel::LOOP_LABELS) ||
               TNodeLabel::_TNodeLabelsAreComplementary($TNodeLabel1, $TNodeLabel2, TNodeLabel::CONDITION_LABELS) ||
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
            case TNodeLabel::CONDITION :
            case TNodeLabel::CONDITION_ELSE :
            case TNodeLabel::END_CONDITION :
            case TNodeLabel::LOOP :
            case TNodeLabel::END_LOOP :
            case TNodeLabel::IGNORED :
            case TNodeLabel::ROOT :
                return true;
            default:
                return false;
        }
    }
}