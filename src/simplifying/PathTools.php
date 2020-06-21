<?php

namespace simplifying;

/**
 * Classe PathManager.
 *
 * @author CHEVRIER Jena-Christophe
 * @package simplifying
 */
class PathTools
{
    /**
     * @param string $path
     * @return bool
     */
    public static function isRelativePath(string $path) : bool {
        $dirs = explode('\\', $path);
        return array_search('.', $dirs) !== false || array_search('..', $dirs) !== false;
    }

    /**
     * @param string $repositoryAbsolutePath
     */
    public static function mkdirIfNotExists(string $repositoryAbsolutePath) : void {
        if(!is_dir($repositoryAbsolutePath)) {
            mkdir($repositoryAbsolutePath);
        }
    }
}