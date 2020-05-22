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
            static::$DB_HOST = MSSQL_HOST;
            static::$connection = new MSSQL_Connection(MSSQL_HOST, MSSQL_USER, MSSQL_PASS);
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