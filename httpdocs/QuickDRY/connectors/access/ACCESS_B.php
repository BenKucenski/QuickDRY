<?php

/**
 * Class ACCESS_B
 */
class ACCESS_B extends ACCESS_Core
{
    protected static $connection =  null;

    protected static function _connect()
    {
        if(is_null(static::$connection)) {
            static::$DB_HOST = ACCESS_FILE;
            static::$connection = new ACCESS_Connection(ACCESSB_FILE, defined('ACCESS_USER') ? ACCESSB_USER : null, defined('ACCESSB_PASS') ? ACCESSB_PASS : null);
        }
    }
}