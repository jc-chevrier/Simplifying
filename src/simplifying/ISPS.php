<?php

/**
 * ISP <=> Initialisation Simplifying Project Script
 * R <=> Repository
 * FS <=> File separator
 * AP <=> Absolute Path
 */

require_once("PathTools.php");
use simplifying\PathTools as PathTools;

chdir(__DIR__);
$currentRAP = realpath("../");
$FS = "\\";

$arrayOfRAP = [];
$arrayOfRAP[] =  $currentRAP . $FS . "app";
$appRAP = $arrayOfRAP[0];
$arrayOfRAP[] = $appRAP . $FS . "models";
$arrayOfRAP[] = $appRAP . $FS . "controllers";
$arrayOfRAP[] = $appRAP . $FS . "resources";
$arrayOfRAP[] = $appRAP . $FS . "views";

$mkdirIfNotExistsR = function($R) {
    PathTools::mkdirIfNotExists($R);
};
array_map($mkdirIfNotExistsR, $arrayOfRAP);