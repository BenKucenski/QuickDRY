<?php

/**
 * Class MSSQL_Base
 */
class MSSQL_B extends MSSQL_Core
{
    protected static $connection =  null;

    protected static function _connect()
    {
        if(is_null(static::$connection)) {
            static::$connection = new MSSQL_Connection(MSSQL_DATACENTER_HOST, MSSQL_DATACENTER_USER, MSSQL_DATACENTER_PASS);
        }
    }
}
