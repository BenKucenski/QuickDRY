<?php

/**
 * Class MSSQL_Base
 */
class MSSQL_A extends MSSQL_Core
{
    protected static $connection =  null;

    protected static function _connect()
    {
        if(is_null(static::$connection)) {
            static::$connection = new MSSQL_Connection(MSSQL_HOST, MSSQL_USER, MSSQL_PASS);
        }
    }
}