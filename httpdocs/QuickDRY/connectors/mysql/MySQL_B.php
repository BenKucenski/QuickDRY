<?php
namespace QuickDRY\Connectors;

/**
 * Class MySQLBase
 */
class MySQL_B extends MySQL_Core
{
    protected static $connection =  null;

    protected static function _connect()
    {
        if(is_null(static::$connection)) {
            static::$DB_HOST = MYSQLB_HOST;
            static::$connection = new MySQL_Connection(MYSQLB_HOST, MYSQLB_USER, MYSQLB_PASS, MYSQLB_PORT);
        }
    }
}