<?php

namespace simplifying;

/**
 * @author CHEVRIER Jean-Christophe.
 */
class DatabaseManager
{
    private static $dbManager;

    public static function getInstance() {
        if(DatabaseManager::$dbManager == null) {
            //TODO
        }
        return DatabaseManager::$dbManager;
    }
}