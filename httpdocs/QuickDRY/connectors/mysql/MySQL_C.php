<?php

/**
 * Class MySQLBase
 */
class MySQL_C extends MySQL_Core
{
    protected static $connection =  null;

    protected static function _connect()
    {
        if(is_null(static::$connection)) {
            static::$DB_HOST = MYSQLC_HOST;
            static::$connection = new MySQL_Connection(MYSQLC_HOST, MYSQLC_USER, MYSQLC_PASS, MYSQLC_PORT);
        }
    }
}