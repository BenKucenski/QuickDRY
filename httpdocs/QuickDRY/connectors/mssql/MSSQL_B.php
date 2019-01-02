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
     * @param $database
     * @param $table_name
     * @return MSSQL_TableColumn[]
     */
    public static function _GetTableColumns($database, $table_name)
    {
        $sql = '
			SELECT
				*
			FROM
				['  . $database . '].INFORMATION_SCHEMA.COLUMNS
			WHERE
				TABLE_NAME=@
		';
        $res = MSSQL_B::Query($sql, [$table_name]);
        /* @var $list MSSQL_TableColumn[] */
        $list = [];
        foreach($res['data'] as $row)
        {
            $t = new MSSQL_TableColumn();
            $t->FromRow($row);
            $list[] = $t;
        }
        return $list;
    }

    public static function _GetTables($database)
    {
        $sql = 'SELECT * FROM ['  . $database . '].information_schema.tables WHERE "TABLE_TYPE" <> \'VIEW\' ORDER BY "TABLE_NAME"';
        $res = MSSQL_B::Query($sql);
        $list = [];
        if($res['error']) {
            return [];
        }
        if(!sizeof($res['data'])) {
            return [];
        }
        foreach($res['data'] as $row)
        {
            $t = $row['TABLE_NAME'];
            if(substr($t,0,strlen('TEMP')) === 'TEMP') {
                continue;
            }

            $list[] = $t;
        }
        return $list;
    }

    public static function _GetDatabases($exclude = null)
    {
        $sql = '
          SELECT name FROM sys.databases
        ';
        $res = MSSQL_B::Query($sql);
        if($res['error']) {
            Halt($res);
        }
        $list = [];
        foreach($res['data'] as $row) {
            if(stristr($row['name'],'$') !== false) {
                continue;
            }
            if($exclude) {
                foreach($exclude as $ex) {
                    if (strcasecmp($row['name'], $ex) == 0) {
                        continue;
                    }
                }
            }
            $list[] = $row['name'];
        }
        return $list;
    }
}
