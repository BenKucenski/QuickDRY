<?php

/**
 * Class MySQLBase
 */
class MySQL_B extends MySQL_Core
{
    protected static $connection =  null;

    protected static function _connect()
    {
        if(is_null(static::$connection)) {
            static::$connection = new MySQL_Connection(MYSQL_1330_HOST, MYSQL_1330_USER, MYSQL_1330_PASS, MYSQL_1330_PORT);
        }
    }
}