<?php

namespace simplifying;

/**
 * Classe PathManager.
 *
 * @author CHEVRIER Jena-Christophe
 * @package simplifying
 */
class PathManager
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
     * @param string $relativePath
     * @param string $localPath
     * @return string
     */
    public static function parseInAbsolutePath(string $relativePath, string $localPath = __DIR__) : string {
        $dirs = explode('\\', $relativePath);
        $i = 0;
        $dir = $dirs[$i];
        if($dir == '.') {
            unset($dirs[$i]);
            $currentDir = explode('\\', $localPath);
            $dirs = array_merge($currentDir, $dirs);
            $i += count($currentDir);
        }
        while($i < count($dirs)) {
            $dir = $dirs[$i];
            if($dir == '..') {
                unset($dirs[$i]);
                unset($dirs[$i - 1]);
                $dirs = array_values($dirs);
                $i--;
            } else {
                $i++;
            }
        }
        $absolutePath = implode('\\', $dirs);
        return $absolutePath;
    }
}