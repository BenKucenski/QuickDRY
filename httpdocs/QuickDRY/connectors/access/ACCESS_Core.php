<?php

class ACCESS_Core extends SQL_Base
{
    protected static $DatabaseTypePrefix = 'access';
    protected static $DB_HOST;
    protected $PRESERVE_NULL_STRINGS = false;  // when true, if a property is set to the string 'null' it will be inserted as 'null' rather than null

    /**
     * @param $database
     * @param $table_name
     * @return ACCESS_TableColumn[]
     */
    public static function _GetTableColumns($table_name)
    {
        //there is no SQL solution for this, so just grab the first row and get the column names from that
        $sql = '
			SELECT
				TOP 1
				*
			FROM
				[' . $table_name . ']
		';
        $res = static::Query($sql, [$table_name]);
        /* @var $list ACCESS_TableColumn[] */
        $list = [];
        foreach ($res['data'] as $row) {
            foreach ($row as $k => $v) {
                $col = ['COLUMN_NAME' => $k];
                $t = new ACCESS_TableColumn();
                $t->FromRow($col);
                $list[] = $t;
            }
        }
        return $list;
    }

    /**
     * @return array
     */
    public static function GetTables()
    {
        static::_connect();
        return static::$connection->GetTables();
    }

    public static function SetDatabase($db_base)
    {
        static::_connect();

        static::$connection->SetDatabase($db_base);
    }

    public static function GetTableColumns($table_name)
    {
        static::_connect();

        return static::$connection->GetTableColumns($table_name);
    }

    public static function GetTableIndexes($table_name)
    {
        static::_connect();

        return static::$connection->GetTableIndexes($table_name);
    }

    public static function GetUniqueKeys($table_name)
    {
        static::_connect();

        return static::$connection->GetUniqueKeys($table_name);
    }

    public static function GetIndexes($table_name)
    {
        static::_connect();

        return static::$connection->GetIndexes($table_name);
    }

    public static function GetForeignKeys($table_name)
    {
        static::_connect();

        return static::$connection->GetForeignKeys($table_name);
    }

    public static function GetLinkedTables($table_name)
    {
        static::_connect();

        return static::$connection->GetLinkedTables($table_name);
    }

    /**
     * @return MSSQL_StoredProc[]
     */
    public static function GetStoredProcs()
    {
        static::_connect();

        return static::$connection->GetStoredProcs();
    }

    /**
     * @param $stored_proc
     * @return MSSQL_StoredProcParam[]
     */
    public static function GetStoredProcParams($stored_proc)
    {
        static::_connect();

        return static::$connection->GetStoredProcParams($stored_proc);
    }


    public static function GetPrimaryKey($table_name)
    {
        static::_connect();

        return static::$connection->GetPrimaryKey($table_name);
    }

    /**
     * @param      $sql
     * @param null $params
     *
     * @return array
     */
    public static function Execute(&$sql, $params = null, $large = false)
    {
        static::_connect();

        if (isset(static::$database)) {
            static::$connection->SetDatabase(static::$database);
        }

        try {
            return static::$connection->Execute($sql, $params, $large);
        } catch (Exception $ex) {
            Debug::Halt($ex);
        }
        return null;
    }

    /**
     * @param $sql
     * @param $params
     * @param $map_function
     * @return mixed
     */
    public static function QueryMap($sql, $params, $map_function)
    {
        static::_connect();

        if (isset(static::$database)) {
            static::$connection->SetDatabase(static::$database);
        }

        return static::$connection->Query($sql, $params, $map_function);
    }

    /**
     * @param      $sql
     * @param null $params
     * @param null $return_type
     * @param bool $objects_only
     *
     * @return array
     */
    public static function Query($sql, $params = null, $objects_only = false, $map_function = null)
    {
        static::_connect();

        if ($objects_only) {
            $return_type = get_called_class();
            $map_function = function ($row) use ($return_type) {
                $c = new $return_type();
                $c->FromRow($row);
                return $c;
            };
        }

        if (isset(static::$database)) {
            static::$connection->SetDatabase(static::$database);
        }

        return static::$connection->Query($sql, $params, $map_function);
    }

    public static function GUID()
    {
        $sql = 'SELECT UPPER(SUBSTRING(master.dbo.fn_varbintohexstr(HASHBYTES(\'MD5\',cast(NEWID() as varchar(36)))), 3, 32)) AS guid';

        static::_connect();

        if (isset(static::$database))
            static::$connection->SetDatabase(static::$database);

        $res = static::$connection->Query($sql);
        if ($res['error']) {
            Halt($res);
        }
        return $res['data'][0]['guid'];
    }

    /**
     * @return int|string
     */
    public static function LastID()
    {
        static::_connect();

        return static::$connection->LastID();
    }

    /**
     * @return array|null
     */
    public function Remove(UserClass &$User)
    {
        if (!$this->CanDelete($User)) {
            return ['error' => 'No Permission'];
        }

        // if this instance wasn't loaded from the database
        // don't try to remove it
        if (!$this->_from_db) {
            return ['error' => 'Invalid Request'];
        }

        if ($this->HasChangeLog()) {
            $uuid = $this->GetUUID();

            if ($uuid) {
                $cl = new ChangeLog();
                $cl->host = static::$DB_HOST;
                $cl->database = static::$database;
                $cl->table = static::$table;
                $cl->uuid = $uuid;
                $cl->changes = json_encode($this->_change_log);
                $cl->user_id = is_object($User) ? $User->GetUUID() : null;
                $cl->created_at = Dates::Timestamp();
                $cl->object_type = static::TableToClass(static::$DatabasePrefix, static::$table, static::$LowerCaseTable, static::$DatabaseTypePrefix);
                $cl->is_deleted = true;
                $cl->Save();
            }
        }


        // rows are removed based on the columns which
        // make the row unique
        if (sizeof(static::$_primary) > 0) {
            foreach (static::$_primary as $column)
                $where[] = $column . ' = ' . MSSQL::EscapeString($this->{$column});
        } else {
            if (sizeof(static::$_unique) > 0) {
                foreach (static::$_unique as $column)
                    $where[] = $column . ' = ' . MSSQL::EscapeString($this->{$column});
            } else {
                return ['error' => 'unique or primary key required'];
            }
        }


        $sql = '
			DELETE FROM
				[' . static::$database . '].dbo.[' . static::$table . ']
			WHERE
				' . implode(' AND ', $where) . '
		';
        $res = self::Execute($sql);

        if (method_exists($this, 'ElasticRemove')) {
            $this->ElasticRemove();
        }

        return $res;
    }

    /**
     * @param $col
     * @param $val
     *
     * @return array
     */
    protected static function _parse_col_val($col, $val)
    {
        // extra + symbols allow us to do AND on the same column
        $col = str_replace('+', '', $col);

        if (substr($val, 0, strlen('{BETWEEN} ')) === '{BETWEEN} ') {
            $val = trim(Strings::RemoveFromStart('{BETWEEN}', $val));
            $val = explode(',', $val);
            $col = $col . ' BETWEEN @ AND @';
        } else
            if (substr($val, 0, strlen('{IN} ')) === '{IN} ') {
                $val = trim(Strings::RemoveFromStart('{IN}', $val));
                $val = explode(',', $val);
                $col = $col . ' IN (' . Strings::StringRepeatCS('@', sizeof($val)) . ')';
            } else
                if (substr($val, 0, strlen('{DATE} ')) === '{DATE} ') {
                    $col = 'CONVERT(date, ' . $col . ') = @';
                    $val = trim(Strings::RemoveFromStart('{DATE}', $val));
                } else
                    if (substr($val, 0, strlen('{YEAR} ')) === '{YEAR} ') {
                        $col = 'DATEPART(yyyy, ' . $col . ') = @';
                        $val = trim(Strings::RemoveFromStart('{YEAR}', $val));
                    } else
                        if (substr($val, 0, strlen('NLIKE ')) === 'NLIKE ') {
                            $col = $col . ' NOT LIKE @';
                            $val = trim(Strings::RemoveFromStart('NLIKE', $val));
                        } else
                            if (substr($val, 0, strlen('NILIKE ')) === 'NILIKE ') {
                                $col = 'LOWER(' . $col . ')' . ' NOT LIKE LOWER(@) ';
                                $val = trim(Strings::RemoveFromStart('NILIKE', $val));
                            } else
                                if (substr($val, 0, strlen('ILIKE ')) === 'ILIKE ') {
                                    $col = 'LOWER(' . $col . ')' . ' ILIKE LOWER(@) ';
                                    $val = trim(Strings::RemoveFromStart('ILIKE', $val));
                                } else
                                    if (substr($val, 0, strlen('LIKE ')) === 'LIKE ') {
                                        $col = $col . ' LIKE @';
                                        $val = trim(Strings::RemoveFromStart('LIKE', $val));
                                    } else
                                        if (substr($val, 0, strlen('<= ')) === '<= ') {
                                            $col = $col . ' <= @ ';
                                            $val = trim(Strings::RemoveFromStart('<=', $val));
                                        } else
                                            if (substr($val, 0, strlen('>= ')) === '>= ') {
                                                $col = $col . ' >= @ ';
                                                $val = trim(Strings::RemoveFromStart('>=', $val));
                                            } else
                                                if (substr($val, 0, strlen('<> ')) === '<> ') {
                                                    $val = trim(Strings::RemoveFromStart('<>', $val));
                                                    if ($val !== 'null') {
                                                        $col = $col . ' <> @ ';
                                                    } else {
                                                        $col = $col . ' IS NOT NULL';
                                                        $val = null;
                                                    }
                                                } else
                                                    if (substr($val, 0, strlen('< ')) === '< ') {
                                                        $col = $col . ' < @ ';
                                                        $val = trim(Strings::RemoveFromStart('<', $val));
                                                    } else
                                                        if (substr($val, 0, strlen('> ')) === '> ') {
                                                            $col = $col . ' > @ ';
                                                            $val = trim(Strings::RemoveFromStart('>', $val));
                                                        } else {
                                                            $col = $col . ' = @ ';
                                                        }

        return ['col' => $col, 'val' => $val];
    }

    /**
     * @param      $id
     * @param null $col
     *
     * @return array|null
     */
    protected static function _Get($id, $col = null)
    {
        $params = [];
        if (is_array($id)) {
            $t = [];
            foreach ($id as $c => $v) {
                $cv = self::_parse_col_val($c, $v);
                $v = $cv['val'];

                if (!is_array($v) && strtolower($v) === 'null') {
                    $t[] = $c . ' IS NULL';
                } else {
                    $t[] = $cv['col'];
                    if (is_array($v)) {
                        foreach ($v as $a) {
                            $params[] = $a;
                        }
                    } else {
                        if(!is_null($v)) {
                            $params[] = $v;
                        }
                    }
                }
            }
            $where_sql = implode(" AND ", $t);
        } else {
            if (is_null($col)) {
                if (isset(static::$_primary[0])) {
                    $col = static::$_primary[0];
                } else {
                    $col = 'id';
                }
            }
            $where_sql = '' . $col . ' = @';
            $params[] = $id;
        }

        $type = get_called_class();

        $sql = '
			SELECT
				*
			FROM
				[' . static::$database . '].dbo.[' . static::$table . ']
			WHERE
				' . $where_sql . '
			';


        if(self::$UseLog) {
            $log = new SQL_Log();
            $log->source = $type;
            $log->start_time = microtime(true);
            $log->query = $sql;
            $log->params = $params;
        }

        $res = static::Query($sql, $params);

        if(self::$UseLog) {
            $log->end_time = microtime(true);
            $log->duration = $log->end_time - $log->start_time;
            self::$Log[] = $log;
        }

        if($res['error']) {
            Halt($res);
        }

        if (isset($res['data'])) {
            foreach ($res['data'] as $r) {
                $t = new $type();
                $t->FromRow($r);

                return $t;
            }
        }
        return null;
    }

    /**
     * @param array $where
     * @param null $order_by
     * @param null $limit
     *
     * @return array
     */
    protected static function _GetAll($where = [], $order_by = null, $limit = null)
    {
        $params = [];

        $sql_order = '';
        if (!is_null($order_by) && is_array($order_by)) {
            $sql_order = [];
            foreach ($order_by as $col => $dir) {
                $sql_order[] .= '' . trim($col) . ' ' . $dir;
            }
            $sql_order = 'ORDER BY ' . implode(', ', $sql_order);
        }


        $sql_where = '1=1';
        if (is_array($where)) {
            $t = [];
            foreach ($where as $c => $v) {
                $cv = self::_parse_col_val($c, $v);
                $v = $cv['val'];

                if (!is_array($v) && strtolower($v) === 'null') {
                    $t[] = $c . ' IS NULL';
                } else {
                    $t[] = $cv['col'];
                    if (is_array($v)) {
                        foreach ($v as $a) {
                            $params[] = $a;
                        }
                    } else {
                        if(!is_null($v)) {
                            $params[] = $v;
                        }
                    }
                }
            }
            $sql_where = implode(" AND ", $t);
        }

        $sql = '
			SELECT
			' . ($limit ? 'TOP ' . $limit : '') . '
				*
			FROM
				[' . static::$database . '].dbo.[' . static::$table . ']
			WHERE
				' . $sql_where . '
				' . $sql_order . '
		';

        if(self::$UseLog) {
            $log = new SQL_Log();
            $log->source = get_called_class();
            $log->start_time = microtime(true);
            $log->query = $sql;
            $log->params = $params;
        }

        $res = static::Query($sql, $params, true);

        if(isset($res['error'])) {
            Halt($res);
        }

        if(self::$UseLog) {
            $log->end_time = microtime(true);
            $log->duration = $log->end_time - $log->start_time;
            self::$Log[] = $log;
        }

        return $res;
    }

    /**
     * @param string $sql_where
     *
     * @return int
     */
    protected static function _GetCount($where = null)
    {
        $sql_where = '1=1';
        $params = null;
        if (is_array($where)) {
            $t = [];
            $params = [];
            foreach ($where as $c => $v) {
                $cv = self::_parse_col_val($c, $v);
                $v = $cv['val'];

                if ($v === 'null')
                    $t[] = '' . $c . ' IS NULL';
                else {
                    $v = $cv['val'];
                    $params[] = $v;
                    $t[] = $cv['col'];
                }
            }
            $sql_where = implode(" AND ", $t);
        }

        $sql = '
			SELECT
				COUNT(*) AS cnt
			FROM
				[' . static::$database . '].dbo.[' . static::$table . ']
			WHERE
				' . $sql_where . '
		';

        if(self::$UseLog) {
            $log = new SQL_Log();
            $log->source = get_called_class();
            $log->start_time = microtime(true);
            $log->query = $sql;
            $log->params = $params;
        }

        $res = static::Query($sql, $params);

        if(self::$UseLog) {
            $log->end_time = microtime(true);
            $log->duration = $log->end_time - $log->start_time;
            self::$Log[] = $log;
        }


        if ($res['error']) {
            Halt($res);
        }

        foreach ($res['data'] as $r) {
            return $r['cnt'];
        }
        return 0;
    }

    /**
     * @param null $order_by
     * @param string $dir
     * @param int $page
     * @param int $per_page
     * @param string $sql_where
     * @param int $left_join
     * @param int $limit
     *
     * @return array
     */
    protected static function _GetAllPaginated($where = null, $order_by = null, $page = 0, $per_page = 0, $left_join = 0, $limit = 0)
    {
        $type = get_called_class();

        $params = [];

        $sql_order = [];
        if (is_array($order_by) && sizeof($order_by)) {
            foreach ($order_by as $col => $dir) {
                $sql_order[] .= '[' . trim($col) . '] ' . $dir;
            }
            $sql_order = 'ORDER BY ' . implode(', ', $sql_order);
        } else {
            $sql_order = '';
        }

        if (!$sql_order) {
            $primary = isset(static::$_primary) ? static::$_primary[0] : 'id';
            $dir = 'asc';
            $sql_order = ' ORDER BY ' . $primary . ' ' . $dir;
        }

        $sql_where = '1=1';
        if (is_array($where) && sizeof($where)) {
            $t = [];
            foreach ($where as $c => $v) {
                $cv = self::_parse_col_val($c, $v);
                $v = $cv['val'];


                if (strtolower($v) === 'null')
                    $t[] = '[' . $c . '] IS NULL';
                else {
                    $v = $cv['val'];
                    $c = $cv['col'];
                    $params[] = $v;
                    $t[] = $c;
                }
            }
            $sql_where = implode(" AND ", $t);
        }

        $sql_left = '';
        if (is_array($left_join)) {
            $sql_left = '';
            foreach ($left_join as $join)
                $sql_left .= 'LEFT JOIN  [' . $join['database'] . '].dbo.[' . $join['table'] . '] AS ' . $join['as'] . ' ON ' . $join['on'] . "\r\n";
        }


        if (!$limit) {
            $sql = '
				SELECT
					COUNT(*) AS num
				FROM
					[' . static::$database . '].dbo.[' . static::$table . ']
					' . $sql_left . '
				WHERE
					' . $sql_where . '
				';
        } else {
            $sql = '
				SELECT COUNT(*) AS num FROM (SELECT TOP ' . $limit . ' * FROM [' . static::$database . '].dbo.[' . static::$table . ']
					' . $sql_left . '
				WHERE
					' . $sql_where . '
				) AS c
			';
        }

        $res = static::Query($sql, $params);
        if ($res['error']) {
            Halt($res);
        }

        $count = isset($res['data'][0]['num']) ? $res['data'][0]['num'] : 0;
        $list = [];
        if ($count > 0) {
            $sql = '
				SELECT
					[' . static::$table . '].*
				FROM
					[' . static::$database . '].dbo.[' . static::$table . ']
					' . $sql_left . '
				WHERE
					 ' . $sql_where . '
				' . $sql_order . '
			';
            if ($per_page != 0) {
                $sql .= '
                OFFSET ' . ($per_page * $page) . ' ROWS FETCH NEXT ' . $per_page . ' ROWS ONLY
				';
            }

            $res = static::Query($sql, $params);

            if ($res['error']) {
                Halt($res);
            }

            foreach ($res['data'] as $r) {
                $t = new $type();
                $t->FromRow($r);
                $list[] = $t;
            }
        }
        return ['count' => $count, 'items' => $list, 'sql' => $sql];
    }

    /**
     * @param $name
     *
     * @return bool
     */
    protected static function IsNumeric($name)
    {
        switch (static::$prop_definitions[$name]['type']) {
            case 'date':
                return false;

            case 'tinyint(1)':
                return true;

            case 'numeric':
            case 'tinyint(1) unsigned':
            case 'int(10) unsigned':
            case 'bigint unsigned':
            case 'decimal(18,2)':
            case 'int(10)':
                return true;

            case 'timestamp':
            case 'datetime':
                return false;
        }
        return false;
    }

    /**
     * @param $name
     * @param $value
     * @param bool $just_checking
     * @return float|int|null
     */
    protected static function StrongType($name, $value, $just_checking = false)
    {
        if (is_array($value)) {
            return null;
        }

        if (is_object($value)) {
            if ($value instanceof DateTime) {
                $value = Dates::Timestamp($value);
            } else {
                return null;
            }
        }

        if (strcasecmp($value, 'null') == 0) {
            if (!$just_checking) {
                if (!static::$prop_definitions[$name]['is_nullable']) {
                    Debug::Halt($name . ' cannot be null');
                }
            }
            return null;
        }


        switch (static::$prop_definitions[$name]['type']) {
            case 'date':
                return $value ? Dates::Datestamp($value) : null;

            case 'tinyint(1)':
                return $value ? 1 : 0;

            case 'decimal(18,2)':
            case 'int(10)':
                return $value * 1.0;

            case 'timestamp':
            case 'datetime':
                return $value ? Dates::Timestamp($value) : null;
        }
        return $value;
    }

    /**
     * @param bool $force_insert
     *
     * @return array
     */
    protected function _Save($force_insert = false)
    {
        /* @var $Web Web */
        global $Web;

        $primary = isset(static::$_primary[0]) ? static::$_primary[0] : 'id';

        if (sizeof(static::$_unique)) { // if we have a unique key defined then check it and load the object if it exists

            foreach (static::$_unique as $cols) {
                $params = [];
                $unique_set = 0;

                foreach ($cols as $col) {
                    if (is_null($this->$col))
                        $params[$col] = 'null';
                    else {
                        $params[$col] = $this->$col;
                        $unique_set++;
                    }
                }
                if ($unique_set && !$this->$primary) {
                    $type = self::TableToClass(static::$DatabasePrefix, static::$table, static::$LowerCaseTable, static::$DatabaseTypePrefix);
                    $t = $type::Get($params);

                    if (!is_null($t)) {
                        if ($t->$primary)
                            $this->$primary = $t->$primary;
                        $vars = $t->ToArray();
                        foreach ($vars as $k => $v)
                            if (isset($this->$k) && is_null($this->$k)) // if the current object value is null, fill it in with the existing object's info
                                $this->$k = $v;
                        break; // only find the first match with unique key definition
                    }
                }
            }
        }

        if (!$this->$primary || $force_insert) {
            $sql = "
				INSERT INTO
					[" . static::$database . "].dbo.[" . static::$table . "]
				";
            $props = [];
            $params = [];
            $qs = [];
            foreach ($this->props as $name => $value) {
                if (strcmp($name, $primary) == 0 && !$this->$primary) continue;

                $props[] = $name;

                $st_value = static::StrongType($name, $value);


                if ((is_null($st_value) || strtolower(trim($value)) === 'null') && !self::IsNumeric($name) && !$this->PRESERVE_NULL_STRINGS) {
                    $qs[] = 'NULL';
                } else {
                    $qs[] = '@';
                    $params[] = '{{{' . $st_value . '}}}'; // necessary to get past the null check in EscapeString
                }

            }
            $sql .= '([' . implode('],[', $props) . ']) VALUES (' . implode(',', $qs) . ')';

            if ($this->$primary && !$force_insert)
                $sql .= "
				WHERE
					" . $primary . " = " . MSSQL::EscapeString($this->$primary) . "
				";

            $res = static::Execute($sql, $params);
        } else {
            $sql = "
				UPDATE
					[" . static::$database . "].dbo.[" . static::$table . "]
                SET
				";
            $props = [];
            $params = [];
            foreach ($this->props as $name => $value) {
                if (strcmp($name, $primary) == 0) continue;

                $st_value = static::StrongType($name, $value);


                if (!is_object($value) && (is_null($st_value) || strtolower(trim($value)) === 'null') && !self::IsNumeric($name) && !$this->PRESERVE_NULL_STRINGS) {
                    $props[] = '[' . $name . '] = NULL';
                } else {
                    $props[] = '[' . $name . '] = @';
                    $params[] = '{{{' . $st_value . '}}}'; // necessary to get past the null check in EscapeString
                }
            }
            $sql .= implode(',', $props);

            if ($this->$primary && !$force_insert)
                $sql .= "
				WHERE
					" . $primary . " = " . MSSQL::EscapeString($this->$primary) . "
				";

            $res = static::Execute($sql, $params);
        }

        if (!$this->$primary)
            $this->$primary = static::LastID();

        if ($this->HasChangeLog()) {
            $uuid = $this->GetUUID();
            if ($uuid) {
                $cl = new ChangeLog();
                $cl->host = static::$DB_HOST;
                $cl->database = static::$database;
                $cl->table = static::$table;
                $cl->uuid = $uuid;
                $cl->changes = json_encode($this->_change_log);
                $cl->user_id = is_object($Web) && $Web->CurrentUser ? $Web->CurrentUser->GetUUID() : null;
                $cl->created_at = Dates::Timestamp();
                $cl->object_type = static::TableToClass(static::$DatabasePrefix, static::$table, static::$LowerCaseTable, static::$DatabaseTypePrefix);
                $cl->is_deleted = false;
                $cl->Save();
            }
        }
        return $res;
    }

}