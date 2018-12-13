<?php

/**
 * Class ACCESS_A
 */
class ACCESS_A extends ACCESS_Core
{
    protected static $connection =  null;

    protected static function _connect()
    {
        if(is_null(static::$connection)) {
            static::$DB_HOST = ACCESS_FILE;
            static::$connection = new ACCESS_Connection(ACCESS_FILE, defined('ACCESS_USER') ? ACCESS_USER : null, defined('ACCESS_PASS') ? ACCESS_PASS : null);
        }
    }
}