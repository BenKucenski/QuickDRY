<?php

class MSSQL_Core extends SQL_Base
{
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
     * @throws Exception
     */
    public static function Execute(&$sql, $params = null, $large = false)
    {
        static::_connect();

        if(isset(static::$database))
            static::$connection->SetDatabase(static::$database);
        return static::$connection->Execute($sql, $params, $large);
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

        if($objects_only) {
            $return_type = get_called_class();
            $map_function = function($row) use ($return_type) {
                $c = new $return_type();
                $c->FromRow($row);
                return $c;
            };
        }

        if(isset(static::$database))
            static::$connection->SetDatabase(static::$database);

        return static::$connection->Query($sql, $params, $map_function);
    }

    public static function GUID()
    {
        $sql = 'SELECT UPPER(SUBSTRING(master.dbo.fn_varbintohexstr(HASHBYTES(\'MD5\',cast(NEWID() as varchar(36)))), 3, 32)) AS guid';

        static::_connect();

        if(isset(static::$database))
            static::$connection->SetDatabase(static::$database);

        $res = static::$connection->Query($sql);
        if($res['error']) {
            CleanHalt($res);
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
        if(!$this->CanDelete($User))
            return null;

        // if this instance wasn't loaded from the database
        // don't try to remove it
        if(!$this->_from_db)
            return null;

        if($this->HasChangeLog())
        {
            $uuid = $this->GetUUID();

            if ($uuid) {
                $key = $uuid . '::' . Timestamp() . '::' . rand(0, 10000);
                $elastic = [];
                $elastic[$key] = [
                    'db_table'    => static::$database . '.' . static::$table,
                    'uuid' => $uuid,
                    'changes'     => json_encode($this->_change_log),
                    'user_id' => is_object($User) ? $User->U_ID : null,
                    'created_at'  => Timestamp(),
                    'object_type' => static::TableToClass(static::$database, static::$table),
                    'is_deleted'  => true,
                ];
                ElasticEdge::Insert('logs', 'change_log', $elastic);
            }
        }


        // rows are removed based on the columns which
        // make the row unique
        if(sizeof(static::$_primary) > 0)
        {
            foreach(static::$_primary as $column)
                $where[] = $column . ' = ' . ms_escape_string($this->{$column});
        }
        else
            if(sizeof(static::$_unique) > 0)
            {
                foreach(static::$_unique as $column)
                    $where[] = $column . ' = ' . ms_escape_string($this->{$column});
            }
            else
                exit('unique or primary key required');


        $sql = '
			DELETE FROM
				[' . static::$database . '].dbo.[' . static::$table . ']
			WHERE
				' . implode(' AND ',$where) . '
		';
        $res = self::Execute($sql);

        if(method_exists($this, 'SolrRemove'))
            $this->SolrRemove();

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
        $col = str_replace('+','',$col);
        $prop_col = $col;

        if(substr($val,0,strlen('NLIKE ')) === 'NLIKE ')
        {
            $col = $col . ' NOT LIKE {{}}';
            $val = trim(str_replace('NLIKE','',$val));
        }
        else
            if(substr($val,0,strlen('NILIKE ')) === 'NILIKE ')
            {
                $col = 'LOWER(' . $col . ')' . ' NOT LIKE LOWER({{}}) ';
                $val = trim(str_replace('NILIKE','',$val));
            }
            else
                if(substr($val,0,strlen('ILIKE ')) === 'ILIKE ')
                {
                    $col = 'LOWER(' . $col . ')' . ' ILIKE LOWER({{}}) ';
                    $val = trim(str_replace('ILIKE','',$val));
                }
                else
                    if(substr($val,0,strlen('LIKE ')) === 'LIKE ')
                    {
                        $col = $col . ' LIKE {{}}';
                        $val = trim(str_replace('LIKE','',$val));
                    }
                    else
                        if(substr($val,0,strlen('<= ')) === '<= ')
                        {
                            $col = $col . ' <= {{}} ';
                            $val = trim(str_replace('<=','',$val));
                        }
                        else
                            if(substr($val,0,strlen('>= ')) === '>= ')
                            {
                                $col = $col . ' >= {{}} ';
                                $val = trim(str_replace('>=','',$val));
                            }
                            else
                                if(substr($val,0,strlen('<> ')) === '<> ')
                                {
                                    $val = trim(str_replace('<>','',$val));
                                    if($val !== 'null')
                                        $col = $col . ' <> {{}} ';
                                    else
                                        $col = $col . ' IS NOT NULL';
                                }
                                else
                                    if(substr($val,0,strlen('< ')) === '< ')
                                    {
                                        $col = $col . ' < {{}} ';
                                        $val = trim(str_replace('<','',$val));
                                    }
                                    else
                                        if(substr($val,0,strlen('> ')) === '> ')
                                        {
                                            $col = $col . ' > {{}} ';
                                            $val = trim(str_replace('>','',$val));
                                        }
                                        else
                                        {
                                            $col = $col . ' = {{}} ';
                                            $val = $val;
                                        }

        return ['col'=>$col,'val'=>$val];
    }

    /**
     * @param      $id
     * @param null $col
     *
     * @return array|null
     */
    protected static function _Get($id, $col = null)
    {
        $where_sql = '1=1';
        $params = [];
        if(is_array($id))
        {
            $t = [];
            foreach($id as $c => $v)
            {
                $cv = self::_parse_col_val($c, $v);
                $v = $cv['val'];

                if($v === 'null')
                    $t[] = '' . $c . ' IS NULL';
                else
                {
                    $v = $cv['val'];
                    if(strtolower($v) !== 'null') {
                        $params[] = $v;
                    }
                    $c = $cv['col'];
                    $t[] = $c;
                }
            }
            $where_sql = implode(" AND ",$t);
        }
        else
        {
            if(is_null($col))
                if(isset(static::$_primary[0]))
                    $col = static::$_primary[0];
                else
                    $col = 'id';
            $where_sql = '' . $col . ' = {{}}';
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


        $res = static::Query($sql, $params);
        if(isset($res['data'])) {
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
     * @param null  $order_by
     * @param null  $limit
     *
     * @return array
     */
    protected static function _GetAll($where= [], $order_by = null, $limit = null)
    {
        $type = get_called_class();
        $params = [];

        $sql_order ='';
        if(!is_null($order_by) && is_array($order_by)) {
            $sql_order = [];
            foreach($order_by as $col => $dir) {
                $sql_order[] .= '' . trim($col) . ' ' . $dir;
            }
            $sql_order = 'ORDER BY ' . implode(', ', $sql_order);
        }


        $sql_where ='1=1';
        if(is_array($where))
        {
            $t = [];
            foreach($where as $c => $v)
            {
                $cv = self::_parse_col_val($c, $v);
                $v = $cv['val'];

                if($v === 'null')
                    $t[] = '' . $c . ' IS NULL';
                else
                {
                    $v = $cv['val'];
                    $params[] = $v;
                    $t[] = $cv['col'];
                }
            }
            $sql_where = implode(" AND ",$t);
        }

        $sql = '
			SELECT
			' . ($limit ? 'TOP ' . $limit  :'' ) . '
				*
			FROM
				[' . static::$database . '].dbo.[' . static::$table . ']
			WHERE
				' . $sql_where . '
				' . $sql_order . '
		';

        $res = static::Query($sql, $params, true);
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

        $res = static::Query($sql, $params);
        if($res['error']) {
            Halt($res);
        }

        foreach ($res['data'] as $r) {
            return $r['cnt'];
        }
        return 0;
    }

    /**
     * @param null   $order_by
     * @param string $dir
     * @param int    $page
     * @param int    $per_page
     * @param string $sql_where
     * @param int    $left_join
     * @param int    $limit
     *
     * @return array
     */
    protected static function _GetAllPaginated($where = null, $order_by = null, $page = 0, $per_page = 0, $left_join = 0, $limit = 0)
    {
        $type = get_called_class();

        $params = [];

        $sql_order ='';
        if(is_array($order_by) && sizeof($order_by)) {
            foreach($order_by as $col => $dir) {
                $sql_order[] .= '[' . trim($col) . '] ' . $dir;
            }
            $sql_order = 'ORDER BY ' . implode(', ', $sql_order);
        }

        if(!$sql_order) {
            $primary = isset(static::$_primary) ? static::$_primary[0] : 'id';
            $dir ='asc';
            $sql_order = ' ORDER BY ' . $primary . ' ' . $dir;
        }

        $sql_where ='1=1';
        if(is_array($where) && sizeof($where))
        {
            $t = [];
            foreach($where as $c => $v)
            {
                $cv = self::_parse_col_val($c, $v);
                $v = $cv['val'];


                if(strtolower($v) === 'null')
                    $t[] = '[' . $c . '] IS NULL';
                else
                {
                    $v = $cv['val'];
                    $c = $cv['col'];
                    $params[] = $v;
                    $t[] = $c;
                }
            }
            $sql_where = implode(" AND ",$t);
        }

        $sql_left = '';
        if(is_array($left_join))
        {
            $sql_left = '';
            foreach($left_join as $join)
                $sql_left .= 'LEFT JOIN  [' . $join['database'] . '].dbo.[' . $join['table'] . '] AS ' . $join['as'] . ' ON ' . $join['on'] . "\r\n";
        }


        if(!$limit) {
            $sql = '
				SELECT
					COUNT(*) AS num
				FROM
					[' . static::$database . '].dbo.[' . static::$table . ']
					' . $sql_left . '
				WHERE
					' . $sql_where . '
				';
        }
        else
        {
            $sql = '
				SELECT COUNT(*) AS num FROM (SELECT * FROM [' . static::$database . '].dbo.[' . static::$table . ']
					' . $sql_left . '
				WHERE
					' . $sql_where . '
				LIMIT ' . $limit . '
				) AS c
			';
        }

        $res = static::Query($sql, $params);
        if($res['error']) {
            Halt($res);
        }

        $count = isset($res['data'][0]['num']) ? $res['data'][0]['num'] : 0;
        $list = [];
        if($count > 0)
        {
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
            if($per_page != 0)
            {
                $sql .= '
                OFFSET ' . ($per_page * $page) . ' ROWS FETCH NEXT ' . $per_page . ' ROWS ONLY
				';
            }

            $res = static::Query($sql, $params);

            if($res['error']) {
                Halt($res);
            }

            foreach($res['data'] as $r)
            {
                $t = new $type();
                $t->FromRow($r);
                $list[] = $t;
            }
        }
        return ['count'=>$count, 'items' => $list, 'sql'=>$sql];
    }

    /**
     * @param $name
     *
     * @return bool
     */
    protected static function IsNumeric($name)
    {
        switch(static::$prop_definitions[$name]['type'])
        {
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
     *
     * @return bool|int|null|string|void
     * @throws Exception
     */
    protected static function StrongType($name, $value, $just_checking = false)
    {
        if(is_object($value) || is_array($value))
            return;

        if(strcasecmp($value,'null') == 0)
        {
            if(!$just_checking) {
                if (!static::$prop_definitions[$name]['nullable']) {
                    throw new Exception($name . ' cannot be null');
                }
            }
            return null;
        }


        switch(static::$prop_definitions[$name]['type'])
        {
            case 'date':
                return $value ? Datestamp($value) : null;

            case 'tinyint(1)':
                return $value ? 1 : 0;

            case 'decimal(18,2)':
            case 'int(10)':
                return $value * 1.0;

            case 'timestamp':
            case 'datetime':
                return $value ? Timestamp($value) : null;
        }
        return $value;
    }

    /**
     * @param bool $force_insert
     *
     * @return array
     * @throws Exception
     */
    protected function _Save($force_insert = false)
    {
        global $User;

        $primary = isset(static::$_primary[0]) ? static::$_primary[0] : 'id';

        if(sizeof(static::$_unique))
        { // if we have a unique key defined then check it and load the object if it exists
            $params = [];
            $unique_set = 0;

            foreach(static::$_unique as $col)
            {
                if(is_null($this->$col))
                    $params[$col] = 'null';
                else
                {
                    $params[$col] = $this->$col;
                    $unique_set++;
                }
            }

            if($unique_set && !$this->$primary)
            {
                $type = self::TableToClass(static::$database, static::$table);
                $t = $type::Get($params);

                if(!is_null($t))
                {
                    if($t->$primary)
                        $this->$primary = $t->$primary;
                    $vars = $t->GetValues($User);
                    foreach($vars as $k => $v)
                        if(isset($this->$k) && is_null($this->$k)) // if the current object value is null, fill it in with the existing object's info
                            $this->$k = $v;
                }
            }
        }

        if(!$this->$primary || $force_insert)
        {
            $sql = "
				INSERT INTO
					[" . static::$database . "].dbo.[" . static::$table . "]
				";
            $props = [];
            $params = [];
            $qs = [];
            foreach($this->props as $name => $value)
            {
                if(strcmp($name,$primary) == 0 && !$this->$primary) continue;

                $props[]= $name;

                $st_value = static::StrongType($name, $value);


                if(is_null($st_value) || strtolower(trim($st_value)) === 'null') {
                    $qs[] = 'NULL';
                } else {
	                $qs[] = '{{}}';
		            $params[] = $st_value;
    			}

            }
            $sql .= '([' . implode('],[', $props) . ']) VALUES (' . implode(',',$qs) . ')';

            if($this->$primary && !$force_insert)
                $sql .= "
				WHERE
					" . $primary . " = " . ms_escape_string($this->$primary) . "
				";

            $res = static::Execute($sql, $params);
        }
        else
        {
            $sql = "
				UPDATE
					[" . static::$database . "].dbo.[" . static::$table . "]
                SET
				";
            $props = [];
            $params = [];
            foreach($this->props as $name => $value)
            {
                if(strcmp($name,$primary) == 0) continue;

                $st_value = static::StrongType($name, $value);

                if(is_null($st_value) || strtolower(trim($st_value)) === 'null')
                    $props[]= '[' . $name . '] = NULL';
                else {
                    $props[] = '[' . $name . '] = {{}}';
                    $params[] = $st_value;
                }
            }
            $sql .= implode(',', $props);

            if($this->$primary && !$force_insert)
                $sql .= "
				WHERE
					" . $primary . " = " . ms_escape_string($this->$primary) . "
				";

            $res = static::Execute($sql, $params);
        }

        if(!$this->$primary)
            $this->$primary = static::LastID();

        if($this->HasChangeLog()) {
            $uuid = $this->GetUUID();
            if ($uuid) {
                $key = $uuid . '::' . Timestamp() . '::' . rand(0, 10000);
                $elastic = [];
                $elastic[$key] = [
                    'db_table'    => static::$database . '.' . static::$table,
                    'uuid' => $uuid,
                    'changes'     => json_encode($this->_change_log),
                    'user_id' => is_object($User) ? $User->U_ID : null,
                    'created_at'  => Timestamp(),
                    'object_type' => static::TableToClass(static::$database, static::$table),
                    'is_deleted'  => false,
                ];
                ElasticEdge::Insert('logs', 'change_log', $elastic);
            }
        }
        return $res;
    }

}