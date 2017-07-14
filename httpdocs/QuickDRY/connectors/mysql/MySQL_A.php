<?php

/**
 * Class MySQLBase
 */
class MySQL_A extends MySQL_Core
{
    protected static $connection =  null;

    protected static function _connect()
    {
        if(is_null(static::$connection)) {
            static::$connection = new MySQL_Connection(MYSQLA_HOST, MYSQLA_USER, MYSQLA_PASS, MYSQLA_PORT);
        }
    }
}