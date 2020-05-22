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
            static::$DB_HOST = MSSQLB_HOST;
            static::$connection = new MSSQL_Connection(MSSQLB_HOST, MSSQLB_USER, MSSQLB_PASS);
        }
    }

    /**
     * @param bool $val
     */
    public static function SetIgnoreDuplicateError($val)
    {
        self::_connect();
        self::$connection->IgnoreDuplicateError = $val;
    }

    /**
     * @return string|null
     */
    public static function _Table()
    {
        return static::$table;
    }
}
