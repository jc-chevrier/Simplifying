<?php

namespace simplifying;

/**
 * Classe Util.
 *
 * @author CHEVRIER Jean-Christophe.
 */
class Util
{
    /**
     * Supprimer des occurences ou une seule ocurrence
     * dans une chaîne de caractères.
     */
    public static function removeOccurrences($search, $string) {
        if(is_array($search)) {
            foreach($search as $element) {
                $string = str_replace($element, "", $string);
            }
            return $string;
        } else {
            $string = str_replace($search, "", $string);
            return $string;
        }
    }
}