<?php

namespace simplifying;

class Util
{
    /**
     * Transformer un tableau en chaine de caractères.
     */
    public static function toString($array) {
        $string = "";
        foreach($array as $element) {
            $string .= $element;
        }
        return $string;
    }


    /**
     * Appliquer une action ($callback) à tous les
     * éléments d'un tableau ($array).
     *
     * Le callback peut disposer en paramètres :
     * de l'élément courant du parcours, de l'indice
     * courant du parcours, d'un accumulateur
     * et du tableau.
     *
     * L'accumulateur est retourné à la fin.
     */
    public static function each($array, $callBack) {
        $acc = null;
        $endIndex = count($array);
        for($i = 0; $i < $endIndex; $i++) {
            $element = $array[$i];
            $acc = $callBack($element, $i, $acc, $array);
        }
        return $acc;
    }



    /**
     * Appliquer une action ($callback) à tous les
     * éléments d'un tableau ($array), en parcourant
     * dans un ordre décroissant le tableau.
     *
     * Le callback peut disposer en paramètres :
     * de l'élément courant du parcours, de l'indice
     * courant du parcours, d'un accumulateur
     * et du tableau.
     *
     * L'accumulateur est retourné à la fin.
     */
    public static function eachDec($array, $callBack) {
        $acc = null;
        $startIndex = count($array) - 1;
        for($i = $startIndex; $i >= 0; $i--) {
            $element = $array[$i];
            $acc = $callBack($element, $i, $acc, $array);
        }
        return $acc;
    }


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