<?php

/**
 * Class MSSQL_Base
 */
class MSSQL_C extends MSSQL_Core
{
    protected static $connection =  null;

    protected static function _connect()
    {
        if(is_null(static::$connection)) {
            static::$DB_HOST = MSSQLC_HOST;
            static::$connection = new MSSQL_Connection(MSSQLC_HOST, MSSQLC_USER, MSSQLC_PASS);
        }
    }
}
