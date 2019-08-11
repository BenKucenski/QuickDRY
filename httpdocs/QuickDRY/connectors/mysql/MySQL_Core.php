<?php

/**
 * Class MySQL_Core
 */
class MySQL_Core extends SQL_Base
{
    protected static $DB_HOST;

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

    public static function CopyInfoSchema()
    {
        static::_connect();

        static::$connection->CopyInfoSchema();
    }

    public static function GetTableColumns($table)
    {
        static::_connect();

        return static::$connection->GetTableColumns($table);
    }

    public static function GetIndexes($table_name)
    {
        static::_connect();

        return static::$connection->GetIndexes($table_name);
    }

    public static function GetUniqueKeys($table)
    {
        static::_connect();

        return static::$connection->GetUniqueKeys($table);
    }

    public static function GetForeignKeys($table)
    {
        static::_connect();

        return static::$connection->GetForeignKeys($table);
    }

    /**
     * @param $table
     * @return MySQL_ForeignKey[]
     */
    public static function GetLinkedTables($table)
    {
        static::_connect();

        return static::$connection->GetLinkedTables($table);
    }

    public static function GetPrimaryKey($table)
    {
        static::_connect();

        return static::$connection->GetPrimaryKey($table);
    }

    public static function GetStoredProcs()
    {
        static::_connect();

        return static::$connection->GetStoredProcs();
    }

    /**
     * @param      $sql
     * @param null $params
     *
     * @return array
     */
    public static function Execute($sql, $params = null, $large = false)
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
     * @param null $params
     * @param null $map_function
     * @return array
     */
    public static function QueryMap($sql, $params = null, $map_function = null)
    {
        $res = self::Query($sql, $params, false, $map_function);
        if (isset($res['error'])) {
            Halt($res);
        }
        return $res;
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

        $return_type = null;
        if ($objects_only)
            $return_type = get_called_class();

        if (isset(static::$database))
            static::$connection->SetDatabase(static::$database);

        return static::$connection->Query($sql, $params, $return_type, $map_function);
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
        if (!$this->CanDelete($User))
            return null;

        // if this instance wasn't loaded from the database
        // don't try to remove it
        if (!$this->_from_db)
            return null;

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


        $params = [];
        // rows are removed based on the columns which
        // make the row unique
        if (sizeof(static::$_primary) > 0) {
            foreach (static::$_primary as $column) {
                $where[] = $column . ' = {{}}';
                $params[] = $this->{$column};
            }
        } else
            if (sizeof(static::$_unique) > 0) {
                foreach (static::$_unique as $column) {
                    $where[] = $column . ' = {{}}';
                    $params[] = $this->{$column};
                }
            } else
                exit('unique or primary key required');


        $sql = '
			DELETE FROM
				' . static::$table . '
			WHERE
				' . implode(' AND ', $where) . '
		';
        $res = static::Execute($sql, $params);

        if (method_exists($this, 'SolrRemove'))
            $this->SolrRemove();

        return $res;
    }

    /**
     * @param $col
     * @param string $val
     *
     * @return array
     */
    protected static function _parse_col_val($col, $val)
    {
        // extra + symbols allow us to do AND on the same column
        $col = str_replace('+', '', $col);
        $col = '`' . $col . '`';

        if (is_array($val)) {
            Halt(['invalid value in query', $col, $val]);
        }
        // adding a space to ensure that "in_" is not mistaken for an IN query
        // and the parameter must START with the special SQL command
        if (substr($val, 0, strlen('{BETWEEN} ')) === '{BETWEEN} ') {
            $val = trim(Strings::RemoveFromStart('{BETWEEN}', $val));
            $val = explode(',', $val);
            $col = $col . ' BETWEEN {{}} AND {{}}';
        } else
            if (substr($val, 0, strlen('{DATE} ')) === '{DATE} ') {
                $col = 'DATE(' . $col . ') = {{}}';
                $val = trim(Strings::RemoveFromStart('{DATE}', $val));
            } else
                if (substr($val, 0, strlen('{YEAR} ')) === '{YEAR} ') {
                    $col = 'YEAR(' . $col . ') = {{}}';
                    $val = trim(Strings::RemoveFromStart('{YEAR}', $val));
                } else
                    if (substr($val, 0, 3) === 'IN ') {
                        $val = explode(',', trim(Strings::RemoveFromStart('IN', $val)));
                        if (($key = array_search('null', $val)) !== false) {
                            $col = '(' . $col . ' IS NULL OR ' . $col . 'IN (' . StringRepeatCS('{{}}', sizeof($val) - 1) . '))';
                            unset($val[$key]);
                        } else {
                            $col = $col . 'IN (' . StringRepeatCS('{{}}', sizeof($val)) . ')';
                        }
                    } else
                        if (substr($val, 0, 6) === 'NLIKE ') {
                            $col = $col . ' NOT LIKE {{}} ';
                            $val = trim(Strings::RemoveFromStart('NLIKE', $val));
                        } else
                            if (substr($val, 0, 7) === 'NILIKE ') {
                                $col = 'LOWER(' . $col . ')' . ' NOT ILIKE {{}} ';
                                $val = strtolower(trim(Strings::RemoveFromStart('NILIKE', $val)));
                            } else
                                if (substr($val, 0, 6) === 'ILIKE ') {
                                    $col = 'LOWER(' . $col . ')' . ' ILIKE {{}} ';
                                    $val = strtolower(trim(Strings::RemoveFromStart('ILIKE', $val)));
                                } else
                                    if (substr($val, 0, 5) === 'LIKE ') {
                                        $col = 'LOWER(' . $col . ')' . ' LIKE LOWER({{}}) ';
                                        $val = trim(Strings::RemoveFromStart('LIKE', $val));
                                    } else
                                        if (stristr($val, '<=') !== false) {
                                            $col = $col . ' <= {{}} ';
                                            $val = trim(Strings::RemoveFromStart('<=', $val));
                                        } else
                                            if (stristr($val, '>=') !== false) {
                                                $col = $col . ' >= {{}} ';
                                                $val = trim(Strings::RemoveFromStart('>=', $val));
                                            } else
                                                if (stristr($val, '<>') !== false) {
                                                    $val = trim(Strings::RemoveFromStart('<>', $val));
                                                    if ($val !== 'null')
                                                        $col = $col . ' <> {{}} ';
                                                    else
                                                        $col = $col . ' IS NOT NULL';
                                                } else
                                                    if (stristr($val, '<') !== false) {
                                                        $col = $col . ' < {{}} ';
                                                        $val = trim(Strings::RemoveFromStart('<', $val));
                                                    } else
                                                        if (stristr($val, '>') !== false) {
                                                            $col = $col . ' > {{}} ';
                                                            $val = trim(Strings::RemoveFromStart('>', $val));
                                                        } else {
                                                            if (strtolower($val) !== 'null') {
                                                                $col = $col . ' = {{}} ';
                                                            } else {
                                                                $col = $col . ' IS NULL ';
                                                            }
                                                        }

        return ['col' => $col, 'val' => $val];
    }

    /**
     * @param $where
     *
     * @return array|null
     */
    protected static function _Get($where)
    {
        if (!is_array($where))
            Halt("$where must be an array");

        $params = [];
        $t = [];
        foreach ($where as $c => $v) {
            $cv = self::_parse_col_val($c, $v);
            $v = $cv['val'];

            if (is_array($v)) {
                foreach ($v as $vv) {
                    $params[] = $vv;
                }
            } else {
                if ($v !== 'null') {
                    $params[] = $v;
                }
            }
            $t[] = $cv['col'];
        }
        $sql_where = implode(" AND ", $t);

        $sql = '
			SELECT
				*
			FROM
				`' . static::$table . '`
			WHERE
				' . $sql_where . '
			';

        $res = static::Query($sql, $params, true);
        foreach ($res as $t) {
            return $t;
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

        $sql_order = [];
        if (is_array($order_by)) {
            foreach ($order_by as $col => $dir) {
                $sql_order[] .= '`' . trim($col) . '` ' . $dir;
            }
            $sql_order = 'ORDER BY ' . implode(', ', $sql_order);
        } else {
            $sql_order = '';
        }

        $sql_where = '1=1';
        if (is_array($where)) {
            $t = [];
            foreach ($where as $c => $v) {
                $c = str_replace('+', '', $c);
                $cv = self::_parse_col_val($c, $v);
                $v = $cv['val'];

                if (is_array($v)) {
                    foreach ($v as $vv) {
                        $params[] = $vv;
                    }
                } else {
                    if ($v !== 'null') {
                        $params[] = $v;
                    }
                }
                $t[] = $cv['col'];
            }
            $sql_where = implode(" AND ", $t);
        }

        $sql = '
			SELECT
				*
			FROM
				`' . static::$table . '`
			WHERE
				' . $sql_where . '
				' . $sql_order . '
		';

        if ($limit) {
            $sql .= ' LIMIT ' . ($limit * 1.0);
        }

        $res = static::Query($sql, $params, true);
        return $res;
    }

    /**
     * @param string $sql_where
     *
     * @return int
     */
    protected static function _GetCount($where = [])
    {
        $sql_where = '1=1';
        $params = [];
        if (is_array($where)) {
            $t = [];
            foreach ($where as $c => $v) {
                $cv = self::_parse_col_val($c, $v);
                $v = $cv['val'];

                if (is_array($v)) {
                    foreach ($v as $vv) {
                        $params[] = $vv;
                    }
                } else {
                    if ($v !== 'null') {
                        $params[] = $v;
                    }
                }
                $t[] = $cv['col'];
            }
            $sql_where = implode(" AND ", $t);
        }

        $sql = '
			SELECT
				COUNT(*) AS cnt
			FROM
				`' . static::$table . '`
			WHERE
				' . $sql_where . '
		';

        $res = static::Query($sql, $params);
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
    protected static function _GetAllPaginated($where = null, $order_by = null, $page, $per_page, $left_join = null, $limit = null)
    {
        $params = [];

        $sql_order = '';
        if (is_array($order_by) && sizeof($order_by)) {
            foreach ($order_by as $col => $dir) {
                if (stristr($col, '.') !== false) {
                    $col = explode('.', $col);
                    $sql_order[] .= '`' . trim($col[0]) . '`.`' . trim($col[1]) . '` ' . $dir;
                } else {
                    $sql_order[] .= '`' . trim($col) . '` ' . $dir;
                }
            }
            $sql_order = 'ORDER BY ' . implode(', ', $sql_order);
        }

        $sql_where = '1=1';
        if (is_array($where) && sizeof($where)) {
            $t = [];
            foreach ($where as $c => $v) {
                $c = str_replace('+', '', $c);
                $c = str_replace('.', '`.`', $c);
                $cv = self::_parse_col_val($c, $v);
                $v = $cv['val'];

                if (strtolower($v) !== 'null') {
                    $params[] = $cv['val'];
                }
                $t[] = $cv['col'];
            }
            $sql_where = implode(" AND ", $t);
        }

        $sql_left = '';
        if (is_array($left_join)) {
            $sql_left = '';
            foreach ($left_join as $join) {
                if (!isset($join['database'])) {
                    Halt($join, 'invalid join');
                }
                $sql_left .= 'LEFT JOIN  `' . $join['database'] . '`.`' . $join['table'] . '` AS ' . $join['as'] . ' ON ' . $join['on']
                    . "\r\n";
            }
        }

        if (!$limit) {
            $sql = '
				SELECT
					COUNT(*) AS num
				FROM
					`' . static::$database . '`.`' . static::$table . '`
					' . $sql_left . '
				WHERE
					' . $sql_where . '
				';
        } else {
            $sql = '
				SELECT COUNT(*) AS num FROM (SELECT * FROM `' . static::$database . '`.`' . static::$table . '`
					' . $sql_left . '
				WHERE
					' . $sql_where . '
				LIMIT ' . $limit . '
				) AS c
			';
        }

        $res = static::Query($sql, $params);
        $count = isset($res['data'][0]['num']) ? $res['data'][0]['num'] : 0;
        $list = [];
        if ($count > 0) {
            $sql = '
				SELECT
					`' . static::$table . '`.*
				FROM
					`' . static::$database . '`.`' . static::$table . '`
					' . $sql_left . '
				WHERE
					 ' . $sql_where . '
					' . $sql_order . '
			';
            if ($per_page != 0) {
                $sql .= '
				LIMIT ' . ($per_page * $page) . ', ' . $per_page . '
				';
            }

            $list = static::Query($sql, $params, true);
        }
        return ['count' => $count, 'items' => $list, 'sql' => $sql, 'res' => $res];
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
     *
     * @return bool|int|null|string
     * @throws Exception
     */
    protected static function StrongType($name, $value)
    {
        if (is_object($value) || is_array($value))
            return null;

        if (strcasecmp($value, 'null') == 0) {
            if (!static::$prop_definitions[$name]['is_nullable']) {
                throw new Exception($name . ' cannot be null');
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

        if (!sizeof($this->_change_log)) {
            return null;
        }

        $primary = isset(static::$_primary[0]) && static::$_primary[0] ? static::$_primary[0] : null;
        $params = [];

        if (sizeof(static::$_unique)) { // if we have a unique key defined then check it and load the object if it exists

            foreach (static::$_unique as $unique) {
                $params = [];
                $unique_set = 0;
                foreach ($unique as $col) {
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
                        foreach ($vars as $k => $v) {
                            if (isset($this->$k) && is_null($this->$k)) {
                                // if the current object value is null, fill it in with the existing object's info
                                $this->$k = $v;
                            }
                        }
                    }
                }
            }
        }


        $changed_only = false;
        if (!$primary || !$this->$primary || $force_insert) {
            $sql = "
				INSERT IGNORE INTO
			";
        } else {
            $changed_only = true;
            // ignore cases where the unique key isn't sufficient to avoid duplicate inserts
            $sql = "
				UPDATE IGNORE
			";
        }

        $sql .= "
					`" . static::$database . "`.`" . static::$table . "`
				SET
				";


        foreach ($this->props as $name => $value) {
            if ($changed_only && !isset($this->_change_log[$name])) {
                continue;
            }

            /**
             * if (!is_null($CurrentUser)) {
             * if (static::$prop_definitions[$name]['type'] === 'datetime') {
             * if ($value) {
             * // this is where we can auto adjust time entries in the database
             * //$value = Timestamp(strtotime(Timestamp($value)) - $CurrentUser->hours_diff * 3600);
             * }
             * }
             * }
             **/

            try {
                $st_value = static::StrongType($name, $value);
            } catch (Exception $ex) {
                Halt($ex);
            }

            if (strcmp($name, $primary) == 0 && $this->$primary && !$force_insert) continue;

            if (is_null($st_value) || strtolower(trim($st_value)) === 'null')
                $sql .= '`' . $name . '` = NULL,';
            else {
                $sql .= '`' . $name . '` = {{}},';
                $params[] = $st_value;
            }
        }

        $sql = substr($sql, 0, strlen($sql) - 1);

        if ($primary && $this->$primary && !$force_insert) {
            $sql .= "
				WHERE
					`" . $primary . "` = {{}}
				";
            $params[] = $this->$primary;
        }

        $res = static::Execute($sql, $params);

        if ($primary && !$this->$primary)
            $this->$primary = $res['last_id'];

        if ($this->HasChangeLog()) {
            $uuid = $this->GetUUID();
            if ($uuid) {
                $cl = new ChangeLog();
                $cl->host = static::$DB_HOST;
                $cl->database = static::$database;
                $cl->table = static::$table;
                $cl->uuid = $uuid;
                $cl->changes = json_encode($this->_change_log);
                $cl->user_id = is_object($Web->CurrentUser) ? $Web->CurrentUser->GetUUID() : null;
                $cl->created_at = Dates::Timestamp();
                $cl->object_type = static::TableToClass(static::$DatabasePrefix, static::$table, static::$LowerCaseTable, static::$DatabaseTypePrefix);
                $cl->is_deleted = false;
                $cl->Save();
            }
        }
        $this->_from_db = true;
        return $res;
    }

    /**
     * @param bool $return_query
     *
     * @return array|SQL_Query
     */
    protected function _Insert($return_query = false)
    {
        $primary = isset(static::$_primary[0]) ? static::$_primary[0] : 'id';

        $sql = '
INSERT INTO
    `' . static::$database . '`.`' . static::$table . '`
';
        $props = [];
        $params = [];
        $qs = [];
        foreach ($this->props as $name => $value) {
            if (strcmp($name, $primary) == 0 && !$this->$primary) {
                continue;
            }

            $props[] = $name;

            $st_value = static::StrongType($name, $value);


            if (!is_object($value) && (is_null($st_value) || strtolower(trim($value)) === 'null') && (self::IsNumeric($name) || (!self::IsNumeric($name) && !$this->PRESERVE_NULL_STRINGS))) {
                $qs[] = 'NULL #' . $name . PHP_EOL;
            } else {
                $qs[] = '{{}} #' . $name . PHP_EOL;
                $params[] = '{{{' . $st_value . '}}}'; // necessary to get past the null check in EscapeString
            }

        }
        $sql .= '(`' . implode('`,`', $props) . '`) VALUES (' . implode(',', $qs) . ')';


        if ($return_query) {
            return new SQL_Query($sql, $params);
        }
        $res = static::Execute($sql, $params);

        return $res;
    }

    /**
     * @param bool $force_insert
     *
     * @return array|SQL_Query
     */
    protected function _Update($return_query)
    {
        if(!sizeof($this->_change_log)) {
            return null;
        }

        $primary = isset(static::$_primary[0]) ? static::$_primary[0] : 'id';

        $sql = '
UPDATE
    `' . static::$database . '`.`' . static::$table . '`
SET
';
        $props = [];
        $params = [];
        foreach ($this->props as $name => $value) {
            if(!isset($this->_change_log[$name])) {
                continue;
            }
            if (strcmp($name, $primary) == 0) continue;

            $st_value = static::StrongType($name, $value);


            if (!is_object($value) && (is_null($st_value) || strtolower(trim($value)) === 'null') && (self::IsNumeric($name) || (!self::IsNumeric($name) && !$this->PRESERVE_NULL_STRINGS))) {
                $props[] = '`' . $name . '` = NULL # ' . $name . PHP_EOL;
            } else {
                $props[] = '`' . $name . '` = {{}} #' . $name . PHP_EOL;
                $params[] = $st_value;
            }
        }
        $sql .= implode(',', $props);

        $sql .= '
WHERE
    ' . $primary . ' = ' . MSSQL::EscapeString($this->$primary) . '
';

        if ($return_query) {
            return new SQL_Query($sql, $params);
        }


        $res = static::Execute($sql, $params);

        if (!$this->$primary)
            $this->$primary = static::LastID();

        return $res;
    }
}