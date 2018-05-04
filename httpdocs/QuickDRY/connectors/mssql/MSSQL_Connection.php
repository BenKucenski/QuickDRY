<?php

/**
 * Class MSSQL_Connection
 */
class MSSQL_Connection
{
    public static $log = [];
    public static $use_log = false;
    public static $keep_files = false;
    public $query_time = 0;
    public $query_count = 0;

    private $_usesqlsrv = false;
    private $_LastConnection;

    protected $db_conns = [];
    protected $db = null;
    protected $current_db = null;

    protected $DB_HOST;
    protected $DB_USER;
    protected $DB_PASS;

    public function __construct($host, $user, $pass)
    {
        $this->DB_HOST = $host;
        $this->DB_USER = $user;
        $this->DB_PASS = $pass;
    }


    private function _connect()
    {
        // p: means persistent
        if (!is_null($this->current_db)) {
            $this->SetDatabase($this->current_db);
        } else {
            $this->SetDatabase('');
        }
    }

    /**
     * @param $database
     * @param $table
     *
     * @return string
     */
    public function TableToClass($database, $table)
    {
        $t = explode('_', $database .'_' . $table);
        $type = '';
        foreach($t as $w)
            $type .= preg_replace('/[^a-z0-9]/si','',ucfirst($w));
        $type .= 'Class';
        if(is_numeric($type[0]))
            $type = 'i' . $type;
        return 'MS' . $type;
    }

    /**
     * @param $db_base
     *
     * @return bool
     */
    public function CheckDatabase($db_base)
    {
        $sql = 'SELECT SCHEMA_NAME FROM INFORMATION_SCHEMA.SCHEMATA WHERE SCHEMA_NAME = "' . $db_base . '"';
        $res = $this->Query($sql);
        return isset($res['data'][0]['SCHEMA_NAME']);
    }

    /**
     * @param $db_base
     *
     * @return bool|void
     */

    public function SetDatabase($db_base)
    {
        $this->_LastConnection['database'] = $db_base;
        $this->_LastConnection['DB_HOST'] = $this->DB_HOST;
        $this->_LastConnection['UID'] = $this->DB_USER;
        $this->_LastConnection['error'] = '';
        $this->_LastConnection['reuse connection'] = '';
        $this->_LastConnection['_usesqlsrv'] = '';

        $time = microtime(true);
        $error = '';

        if($db_base && strcmp($this->current_db,$db_base) == 0 && $this->db) {
            $this->_LastConnection['reuse connection'] = $this->current_db . ' = ' . $db_base;
            return;
        }


        if(!$this->_usesqlsrv && IsWindows() && function_exists('sqlsrv_connect')) {
            $this->_usesqlsrv = true;
            $this->_LastConnection['_usesqlsrv'] = 'true';
        }

        if(!isset($this->db_conns[$db_base]) || is_null($this->db_conns[$db_base])) {
            try {
                if(!$this->_usesqlsrv) {
                    $this->db_conns[$db_base] = mssql_connect($this->DB_HOST, $this->DB_USER, $this->DB_PASS);
                    if(!$this->db_conns[$db_base]) {
                        Halt(['Could not connect', $this->DB_HOST, $this->DB_USER]);
                    }
                    mssql_min_error_severity(1);
                    if ($db_base) {
                        $error = mssql_get_last_message();
                        if($error) {
                            throw new Exception($error);
                        }
                        mssql_select_db($db_base, $this->db_conns[$db_base]);
                    }
                } else {
                    sqlsrv_errors(SQLSRV_ERR_ERRORS);
                    if(stristr($db_base,'.') !== false) { // linked server support
                        $this->db_conns[$db_base] = sqlsrv_connect($this->DB_HOST, ["UID" => $this->DB_USER, "PWD" => $this->DB_PASS]);
                    } else {
                        $this->db_conns[$db_base] = sqlsrv_connect($this->DB_HOST, ["Database" => $db_base, "UID" => $this->DB_USER, "PWD" => $this->DB_PASS]);
                    }
                    $error = print_r(sqlsrv_errors(),true);
                    if(isset($error['message']) && $error['message']) {
                        throw new Exception($error);
                    }
                }
            } catch(Exception $e) {
                // Log exception
                Halt($e);
            }
        }

        $this->_LastConnection['error'] = $error;

        $this->db = $this->db_conns[$db_base];
        if(!$this->db) {
            Halt($this->_LastConnection);
        }
        $this->current_db = $db_base;
        $time = microtime(true) - $time;
        $this->query_time += $time;
        self::Log($db_base, null, $time, $error);

        $this->_LastConnection['current_db'] = $db_base;

        /**
        if(!$this->_usesqlsrv) {
            //mssql_query('SET ARITHABORT ON', $this->db); // https://msdn.microsoft.com/en-us/library/ms190306.aspx
        }

         * **/
        /*
            // this query should show "1" for arithabort when run from SSMS
            select
                arithabort,
                *
            from
                sys.dm_exec_sessions
            where
                session_id > 50

         */
    }

    /**
     * @param      $sql
     * @param      $params
     * @param int  $time
     * @param null $err
     */
    private function Log(&$sql, $params, $time = 0, $err = null)
    {
        if(!static::$use_log)
            return;

        if(!$sql) {
            Halt('empty query');
        }

        $this->query_time += $time;
        $this->query_count++;
        if(!isset(self::$log[$sql])) {
            self::$log[$sql] = [
                'params' => [],
                'err' => [],
                'time' => [],
                'total_time' => 0,
                'avg_time' => 0,
                'count' => 0,
            ];
        }
        self::$log[$sql]['params'][] = $params;
        if($err) {
            self::$log[$sql]['err'][] = $err;
        }
        self::$log[$sql]['time'][] = $time;
        self::$log[$sql]['total_time']+=$time;
        self::$log[$sql]['count']++;
        self::$log[$sql]['avg_time'] =self::$log[$sql]['total_time'] / self::$log[$sql]['count'];


    }

    private static function SQLErrorsToString()
    {
        $errs = sqlsrv_errors();

        $res = [];

        foreach($errs as $err) {
            $res[] = $err['SQLSTATE'] . ', ' . $err['code'] . ': ' . $err['message'];
        }
        return implode("\r\n", $res);
    }

    /**
     * @param       $sql
     * @param array $params
     * @param null  $return_type
     * @param bool  $objects_only
     *
     * @return array
     */
    private function QueryWindows($sql, $params = [], $map_function = null)
    {
        Metrics::Start('MSSQL');

        $start = microtime(true);

        $returnval = [
            'error' => 'command not executed',
            'numrows' => 0,
            'data' => [],
            'sql' => $sql,
            'params' => $params
        ];

        $query = MSSQL::EscapeQuery($sql, $params);
        $returnval['query'] = $query;

        $this->_connect();

        // If still no link, then the query will not run...
        if(!$this->db)
        {
            // Notify that DB is crashed
            $returnval['error'] = ['QueryWindows No DB Connection', $this->_LastConnection, $this->db_conns];
            Metrics::Stop('MSSQL');
            return $returnval;
        }
        try
        {
            $list = [];
            $result = sqlsrv_query($this->db, $query);

            if(!$result)
            {
                $returnval = ['error'=>static::SQLErrorsToString(),'query'=>$query,'params'=>$params];
                if($returnval['error'] && defined('MYSQL_EXIT_ON_ERROR') && MYSQL_EXIT_ON_ERROR)
                    Halt($returnval);
            }
            else
            {
                while($r = sqlsrv_fetch_array($result, SQLSRV_FETCH_ASSOC)) {
                    $list[] = is_null($map_function) ? $r : call_user_func($map_function, $r);
                }
                $more = [];
                $i = 0;
                while (sqlsrv_next_result($result)) {
                    $more[$i] = [];
                    while($r = sqlsrv_fetch_array($result, SQLSRV_FETCH_ASSOC)) {
                        $more[$i][] = is_null($map_function) ? $r : call_user_func($map_function, $r);
                    }
                    $i++;
                }
                $returnval = [
                    'data'=>$list,
                    'error'=>'',
                    'time'=>microtime(true) - $start,
                    'query'=>$query,
                    'params'=>$params,
                    'sql'=>$sql
                ];
                if(sizeof($more)) {
                    $returnval['more'] = $more;
                }
                sqlsrv_free_stmt( $result);
            }
        }
        catch(Exception $e)
        {

            $returnval['error'] = 'Exception: '.$e->getMessage();
            $returnval['sql'] = print_r([$sql,$params],true);
            Metrics::Stop('MSSQL');
            if(defined('MYSQL_EXIT_ON_ERROR') && MYSQL_EXIT_ON_ERROR)
                Halt($returnval);
            return $returnval;
        }

        $t = microtime(true) - $start;
        Metrics::Stop('MSSQL');
        $this->Log($sql, $params, $t, $returnval['error']);

        if(!$map_function || $returnval['error']) {
            return $returnval;
        }
        return $returnval['data'];
    }

    /**
     * @param       $sql
     * @param array $params
     * @param null  $return_type
     * @param bool  $objects_only
     *
     * @return array
     */
    public function Query($sql, $params = [], $map_function = null)
    {
        $this->_connect();

        if($this->_usesqlsrv)
            return $this->QueryWindows($sql, $params, $map_function);

        Metrics::Start('MSSQL');

        $start = microtime(true);

        $returnval = [
            'error' => 'command not executed',
            'numrows' => 0,
            'data' => [],
            'sql' => $sql,
            'params' => $params
        ];

        $query = MSSQL::EscapeQuery($sql, $params);
        $returnval['query'] = $query;

        // If still no link, then the query will not run...
        if(!$this->db)
        {
            Metrics::Stop('MSSQL');
            // Notify that DB is crashed
            $returnval['error'] = 'Query No DB Connection';
            return $returnval;
        }
        try
        {
            $list = [];
            $result = false;
            $query = MSSQL::EscapeQuery($sql, $params);

            if(defined('QUERY_RETRY')) {
                $count = 0;
                while (!$result && $count <= QUERY_RETRY) {
                    $result = @mssql_query($query, $this->db);
                    if (!$result) {
                        if (mssql_get_last_message()) {
                            break;
                        } else {
                            $this->db = null;
                            $this->db_conns[$this->current_db] = null;
                            sleep(30);
                            $this->_connect();
                        }
                    }
                    $count++;
                }
            } else {
                $result = @mssql_query($query, $this->db);
            }
            if(!$result)
            {
                $returnval = ['error'=>print_r(mssql_get_last_message(),true),'query'=>$query,'params'=>$params, 'sql' => $sql];
                if($returnval['error']) {
                    Halt($returnval);
                }
            }
            else
            {
                while($r = mssql_fetch_assoc($result)) {
                    $list[] = is_null($map_function) ? $r : call_user_func($map_function, $r);
                }
                $returnval = ['data'=>$list,'error'=>'', 'time'=>microtime(true) - $start,'query'=>$sql,'params'=>$params];
            }
        }
        catch(Exception $e)
        {

            $returnval['error'] = 'Exception: '.$e->getMessage();
            $returnval['sql'] = print_r([$sql,$params],true);
            if(defined('MYSQL_EXIT_ON_ERROR') && MYSQL_EXIT_ON_ERROR)
                Halt($returnval);
            Metrics::Stop('MSSQL');
            return $returnval;
        }

        $t = microtime(true) - $start;
        Metrics::Stop('MSSQL');

        $this->Log($sql, $params, $t, $returnval['error']);

        if(!$map_function || $returnval['error']) {
            return $returnval;
        }
        return $returnval['data'];
    }

        /**
     * @param       $sql
     * @param array $params
     *
     * @return array
     */
    public function ExecuteWindows(&$query, $large = false)
    {
        Metrics::Start('MSSQL');

        $start = microtime(true);

        $returnval = ['error' => 'command not executed',
                      'numrows' => 0,
                      'data'  => [],
                      'query' => $query
        ];

        $this->_connect();


        // If still no link, then the query will not run...
        if (!$this->db) {
            // Notify that DB is crashed
            $returnval['error'] = 'ExecuteWindows No DB Connection';

            return $returnval;
        }
        try {
            if ($large) {
                if(!is_dir('sql')) {
                    mkdir('sql');
                }
                $fname = 'sql/' . time() . rand(0, 10000) . '.mssql.txt';

                $fp = fopen($fname, 'w');
                while(!$fp) {
                    sleep(1);
                    $fname = 'sql/' . time() . rand(0, 1000000) . '.mssql.txt';
                    $fp = fopen($fname, 'w');
                }
                fwrite($fp, $query);
                fclose($fp);
                $output = [];
                // -x turns off variable interpretation - must be set
                // https://docs.microsoft.com/en-us/sql/tools/sqlcmd-utility
                // adding -l 0 to avoid login timeout errors
                $cmd = 'sqlcmd  -l 0 -a 32767 -x -U' . $this->DB_USER . ' -P"' . $this->DB_PASS . '" -S' . $this->DB_HOST . ' -i"' . $fname . '"';

                if(self::$keep_files) {
                    Log::Insert($cmd, true);
                }
                $res = exec($cmd, $output);
                if(self::$keep_files) {
                    Log::Insert($output, true);
                }

                $returnval['exec'] = $res . PHP_EOL . PHP_EOL . implode(
                        PHP_EOL, $output
                    );
                $returnval['error'] = '';
                if(!self::$keep_files) {
                    unlink($fname);
                }
            } else {
                $result = sqlsrv_query($this->db, $query);
                if (!$result) {
                    $returnval = ['error' => static::SQLErrorsToString(),
                                  'query' => $query];
                } else {
                    $returnval['error'] = '';
                    $returnval['numrows'] = sqlsrv_rows_affected($result);
                }
                if ($result) {
                    sqlsrv_free_stmt($result);
                }
            }
        } catch (Exception $e) {
            Metrics::Stop('MSSQL');
            $returnval['error'] = 'Exception: ' . $e->getMessage();
            $returnval['query'] = $query;

            return $returnval;
        }

        $t = microtime(true) - $start;
        Metrics::Stop('MSSQL');
        if(!$large) {
            $this->Log($query, null, $t, $returnval['error']);
        }

        return $returnval;
    }

    /**
     * @param       $sql
     * @param array $params
     *
     * @return array
     */
    public function Execute(&$sql, $params = [], $large = false)
    {
        Metrics::Start('MSSQL');

        $start = microtime(true);
        if(!is_null($params) && sizeof($params)) {
            $query = MSSQL::EscapeQuery($sql, $params);
        } else {
            $query = $sql;
        }

        $returnval = [
            'error' => 'command not executed',
            'numrows' => 0,
            'data' => [],
            'query' => $query,
        ];

        $this->_connect();

        if($this->_usesqlsrv) {
            return $this->ExecuteWindows($query, $large);
        }


        // If still no link, then the query will not run...
        if(!$this->db)
        {
            // Notify that DB is crashed
            $returnval['error'] = 'Execute No DB Connection';
            return $returnval;
        }
        try
        {
            $result = mssql_query($query, $this->db);
            if(!$result)
                $returnval = ['error'=>print_r(mssql_get_last_message()),'query'=>$query];
            else {
                $returnval['error'] = '';
                $returnval['numrows'] = mssql_rows_affected($this->db);
            }
        }
        catch(Exception $e)
        {

            $returnval['error'] = 'Exception: '.$e->getMessage();
            $returnval['sql'] = $query;
            return $returnval;
        }

        $t = microtime(true) - $start;
        Metrics::Stop('MSSQL');
        $this->Log($sql, $params, $t, $returnval['error']);

        return $returnval;
    }

    /**
     * @return null
     */
    public function LastID()
    {
        $sql = '
					SELECT SCOPE_IDENTITY() AS lid
				';
        $res = $this->Query($sql);
        return isset($res['data'][0]['lid']) ? $res['data'][0]['lid'] : null;
    }

    /**
     * @return array
     */
    public function GetTables()
    {
        $sql = 'SELECT * FROM '  . $this->current_db. '.information_schema.tables WHERE "TABLE_TYPE" <> \'VIEW\' ORDER BY "TABLE_NAME"';
        $res = $this->Query($sql);
        $list = [];
        foreach($res['data'] as $row)
        {
            $t = $row['TABLE_NAME'];
            if(substr($t,0,strlen('TEMP')) === 'TEMP')
                continue;

            $list[] = $t;
        }
        return $list;
    }

    /**
     * @param $table_name
     *
     * @return array
     */
    public function GetTableColumns($table_name)
    {
        $sql = '
			SELECT
				*
			FROM
				'  . $this->current_db. '.INFORMATION_SCHEMA.COLUMNS
			WHERE
				TABLE_NAME=@
		';
        $res = $this->Query($sql, [$table_name]);
        $list = [];
        foreach($res['data'] as $row)
        {
            $t = new MSSQL_TableColumn();
            $t->FromRow($row);
            $list[] = $t;
        }
        return $list;
    }

    /**
     * @param $table_name
     *
     * @return array
     */
    public function GetTableIndexes($table_name)
    {
        $sql = '
			SELECT
				OBJECT_SCHEMA_NAME(T.[object_id],DB_ID()) AS [Schema],
  				T.[name] AS [table_name],
				I.[name] AS [index_name],
				AC.[name] AS [column_name],
  				I.[type_desc],
				I.[is_unique],
				I.[data_space_id],
				I.[ignore_dup_key],
				I.[is_primary_key],
  				I.[is_unique_constraint],
				I.[fill_factor],
				I.[is_padded],
				I.[is_disabled],
				I.[is_hypothetical],
  				I.[allow_row_locks],
				I.[allow_page_locks],
				IC.[is_descending_key],
				IC.[is_included_column]
			FROM
				'  . $this->current_db. '.sys.[tables] AS T
  			INNER JOIN '  . $this->current_db. '.sys.[indexes] I ON T.[object_id] = I.[object_id]
  			INNER JOIN '  . $this->current_db. '.sys.[index_columns] IC ON I.[object_id] = IC.[object_id]
  			INNER JOIN '  . $this->current_db. '.sys.[all_columns] AC ON T.[object_id] = AC.[object_id] AND IC.[column_id] = AC.[column_id]
			WHERE
				T.[is_ms_shipped] = 0
				AND I.[type_desc] <> \'HEAP\'
				AND T.[name] = @
			ORDER BY
				T.[name],
				I.[index_id],
				IC.[key_ordinal]
		';
        $res =$this->Query($sql, [$table_name]);
        $indexes = [];
        foreach($res['data'] as $row)
        {
            $indexes[$row['index_name']]['is_unique'] = $row['is_unique'];
            $indexes[$row['index_name']]['is_primary_key'] = $row['is_primary_key'];
            $indexes[$row['index_name']]['columns'][] = $row['column_name'];
        }
        return $indexes;
    }

    private static $_UniqueKeys = null;
    private static $_Indexes = null;
    /**
     * @param $table_name
     *
     * @return []
     */
    public function GetIndexes($table_name)
    {
        if(is_null(self::$_Indexes)) {
            $this->GetUniqueKeys($table_name);
        }
        if(!isset(self::$_Indexes[$table_name])) {
            self::$_Indexes[$table_name] = [];
        }
        return self::$_Indexes[$table_name];
    }

    /**
     * @param $table_name
     * @return mixed
     */
    public function GetUniqueKeys($table_name)
    {
        if(is_null(self::$_UniqueKeys)) {
            self::$_UniqueKeys = [];
            self::$_Indexes = [];
            // https://stackoverflow.com/questions/765867/list-of-all-index-index-columns-in-sql-server-db
            $sql = '
SELECT 
     TableName = t.name,
     IndexName = ind.name,
     IndexId = ind.index_id,
     ColumnId = ic.index_column_id,
     ColumnName = col.name,
     ind.*,
     ic.*,
     col.* 
FROM 
     '  . $this->current_db. '.sys.indexes ind 
INNER JOIN 
     '  . $this->current_db. '.sys.index_columns ic ON  ind.object_id = ic.object_id and ind.index_id = ic.index_id 
INNER JOIN 
     '  . $this->current_db. '.sys.columns col ON ic.object_id = col.object_id and ic.column_id = col.column_id 
INNER JOIN 
     '  . $this->current_db. '.sys.tables t ON ind.object_id = t.object_id 

ORDER BY 
     t.name, ind.name, ind.index_id, ic.index_column_id        
        
        ';

            $res = $this->Query($sql);
            if($res['error']) {
                Halt($res);
            }
            foreach($res['data'] as $row) {
                if($row['is_primary_key']) {
                    continue;
                }
                if($row['is_unique']) {
                    if(!isset(self::$_UniqueKeys[$row['TableName']][$row['IndexName']])) {
                        self::$_UniqueKeys[$row['TableName']][$row['IndexName']] = [];
                    }
                    self::$_UniqueKeys[$row['TableName']][$row['IndexName']][] = $row['ColumnName'];
                } else {
                    if(!isset(self::$_Indexes[$row['TableName']][$row['IndexName']])) {
                        self::$_Indexes[$row['TableName']][$row['IndexName']] = [];
                    }
                    self::$_Indexes[$row['TableName']][$row['IndexName']][] = $row['ColumnName'];
                }
            }
        }
        if(!isset(self::$_UniqueKeys[$table_name])) {
            self::$_UniqueKeys[$table_name] = [];
        }
        return self::$_UniqueKeys[$table_name];
    }

    /**
     * @param $table_name
     *
     * @return array
     */

    private static $_ForeignKeys = null;

    public function GetForeignKeys($table_name)
    {
        if (!isset(self::$_ForeignKeys[$this->current_db])) {


            $sql = '
			SELECT
			     KCU1.CONSTRAINT_NAME AS FK_CONSTRAINT_NAME
			    ,KCU1.TABLE_NAME AS FK_TABLE_NAME
			    ,KCU1.COLUMN_NAME AS FK_COLUMN_NAME
			    ,KCU1.ORDINAL_POSITION AS FK_ORDINAL_POSITION
			    ,KCU2.CONSTRAINT_NAME AS REFERENCED_CONSTRAINT_NAME
			    ,KCU2.TABLE_NAME AS REFERENCED_TABLE_NAME
			    ,KCU2.COLUMN_NAME AS REFERENCED_COLUMN_NAME
			    ,KCU2.ORDINAL_POSITION AS REFERENCED_ORDINAL_POSITION
			FROM '  . $this->current_db. '.INFORMATION_SCHEMA.REFERENTIAL_CONSTRAINTS AS RC

			LEFT JOIN '  . $this->current_db. '.INFORMATION_SCHEMA.KEY_COLUMN_USAGE AS KCU1
			    ON KCU1.CONSTRAINT_CATALOG = RC.CONSTRAINT_CATALOG
			    AND KCU1.CONSTRAINT_SCHEMA = RC.CONSTRAINT_SCHEMA
			    AND KCU1.CONSTRAINT_NAME = RC.CONSTRAINT_NAME

			LEFT JOIN '  . $this->current_db. '.INFORMATION_SCHEMA.KEY_COLUMN_USAGE AS KCU2
			    ON KCU2.CONSTRAINT_CATALOG = RC.UNIQUE_CONSTRAINT_CATALOG
			    AND KCU2.CONSTRAINT_SCHEMA = RC.UNIQUE_CONSTRAINT_SCHEMA
			    AND KCU2.CONSTRAINT_NAME = RC.UNIQUE_CONSTRAINT_NAME
			    AND KCU2.ORDINAL_POSITION = KCU1.ORDINAL_POSITION

		';
            $res = $this->Query($sql);
            self::$_ForeignKeys[$this->current_db] = [];
            foreach ($res['data'] as $row) {
                if (!isset(self::$_ForeignKeys[$this->current_db][$row['FK_TABLE_NAME']])) {
                    self::$_ForeignKeys[$this->current_db][$row['FK_TABLE_NAME']] = [];
                }

                if (!isset(self::$_ForeignKeys[$this->current_db][$row['FK_TABLE_NAME']][$row['FK_CONSTRAINT_NAME']])) {
                    $fk = new MSSQL_ForeignKey();
                    $fk->FromRow($row);
                } else {
                    $fk = self::$_ForeignKeys[$this->current_db][$row['FK_TABLE_NAME']][$row['FK_CONSTRAINT_NAME']];
                    $fk->AddRow($row);
                }

                self::$_ForeignKeys[$this->current_db][$row['FK_TABLE_NAME']][$row['FK_CONSTRAINT_NAME']] = $fk;
            }
        }
        if (!isset(self::$_ForeignKeys[$this->current_db][$table_name])) {
            self::$_ForeignKeys[$this->current_db][$table_name] = [];
        }

        return self::$_ForeignKeys[$this->current_db][$table_name];
    }

    private static $_LinkedTables = null;

    public function GetLinkedTables($table_name)
    {
        if (!isset(self::$_LinkedTables[$this->current_db])) {
            $sql = '
			SELECT
			     KCU2.CONSTRAINT_NAME AS FK_CONSTRAINT_NAME
			    ,KCU2.TABLE_NAME AS FK_TABLE_NAME
			    ,KCU2.COLUMN_NAME AS FK_COLUMN_NAME
			    ,KCU2.ORDINAL_POSITION AS FK_ORDINAL_POSITION

			    ,KCU1.CONSTRAINT_NAME AS REFERENCED_CONSTRAINT_NAME
			    ,KCU1.TABLE_NAME AS REFERENCED_TABLE_NAME
			    ,KCU1.COLUMN_NAME AS REFERENCED_COLUMN_NAME
			    ,KCU1.ORDINAL_POSITION AS REFERENCED_ORDINAL_POSITION
			FROM '  . $this->current_db. '.INFORMATION_SCHEMA.REFERENTIAL_CONSTRAINTS AS RC

			LEFT JOIN '  . $this->current_db. '.INFORMATION_SCHEMA.KEY_COLUMN_USAGE AS KCU1
			    ON KCU1.CONSTRAINT_CATALOG = RC.CONSTRAINT_CATALOG
			    AND KCU1.CONSTRAINT_SCHEMA = RC.CONSTRAINT_SCHEMA
			    AND KCU1.CONSTRAINT_NAME = RC.CONSTRAINT_NAME

			LEFT JOIN '  . $this->current_db. '.INFORMATION_SCHEMA.KEY_COLUMN_USAGE AS KCU2
			    ON KCU2.CONSTRAINT_CATALOG = RC.UNIQUE_CONSTRAINT_CATALOG
			    AND KCU2.CONSTRAINT_SCHEMA = RC.UNIQUE_CONSTRAINT_SCHEMA
			    AND KCU2.CONSTRAINT_NAME = RC.UNIQUE_CONSTRAINT_NAME
			    AND KCU2.ORDINAL_POSITION = KCU1.ORDINAL_POSITION

		';
            $res = $this->Query($sql);

            if (isset($res['data'])) {
                foreach ($res['data'] as $row) {
                    if (!isset($fks[$row['REFERENCED_CONSTRAINT_NAME']])) {
                        $fk = new MSSQL_ForeignKey();
                        $fk->FromRow($row);
                    } else {
                        $fk = self::$_LinkedTables[$this->current_db][$row['FK_TABLE_NAME']][$row['REFERENCED_CONSTRAINT_NAME']];
                        $fk->AddRow($row);
                    }

                    if (!isset(self::$_LinkedTables[$this->current_db][$row['FK_TABLE_NAME']])) {
                        self::$_LinkedTables[$this->current_db][$row['FK_TABLE_NAME']] = [];
                    }
                    self::$_LinkedTables[$this->current_db][$row['FK_TABLE_NAME']][$row['REFERENCED_CONSTRAINT_NAME']] = $fk;
                }
            }
        }
        if (!isset(self::$_LinkedTables[$this->current_db][$table_name])) {
            self::$_LinkedTables[$this->current_db][$table_name] = [];
        }

        return self::$_LinkedTables[$this->current_db][$table_name];
    }

    /**
     * @param $table_name
     *
     * @return array
     */
    public function GetPrimaryKey($table_name)
    {
        $sql = '
			SELECT
				column_name
			FROM
				'  . $this->current_db. '.INFORMATION_SCHEMA.KEY_COLUMN_USAGE
			WHERE
				OBJECTPROPERTY(OBJECT_ID(constraint_name), \'IsPrimaryKey\') = 1
				AND table_name = @
		';
        $res = $this->Query($sql, [$table_name]);
        if($res['error']) {
            Halt($res);
        }
        $list = [];
        foreach($res['data'] as $col) {
            $list[] = $col['column_name'];
        }
        return $list;
    }

    /**
     * @return MSSQL_StoredProc[]
     */
    public function GetStoredProcs()
    {
        $sql = '
select * 
  from '  . $this->current_db. '.information_schema.routines 
 where routine_type = \'PROCEDURE\'
 ORDER BY SPECIFIC_NAME        
        ';
        /* @var $res MSSQL_StoredProc[] */
        $res = $this->Query($sql, null, function($row) {
            return new MSSQL_StoredProc($row);
        });
        if(isset($res['error'])) {
            CleanHalt($res);
        }
        return $res;
    }

    private $_StoredProcParams = [];

    /**
     * @param $stored_proc
     * @return MSSQL_StoredProcParam[]
     */
    public function GetStoredProcParams($stored_proc)
    {
        if(sizeof($this->_StoredProcParams)) {
            return isset($this->_StoredProcParams[$stored_proc]) ? $this->_StoredProcParams[$stored_proc] : [];
        }

        $sql = '
select  
  \'StoredProc\' = object_name(object_id),
   \'Parameter_name\' = name,  
   \'Type\'   = type_name(user_type_id),  
   \'Length\'   = max_length,  
   \'Prec\'   = case when type_name(system_type_id) = \'uniqueidentifier\' 
              then precision  
              else OdbcPrec(system_type_id, max_length, precision) end,  
   \'Scale\'   = OdbcScale(system_type_id, scale),  
   \'Param_order\'  = parameter_id,  
   \'Collation\'   = convert(sysname, 
                   case when system_type_id in (35, 99, 167, 175, 231, 239)  
                   then ServerProperty(\'collation\') end)  

  from '  . $this->current_db. '.sys.parameters   
  ORDER BY parameter_id
        ';

        $res = $this->Query($sql);
        foreach($res['data'] as $row) {
            if(!isset($this->_StoredProcParams[$row['StoredProc']])) {
                $this->_StoredProcParams[$row['StoredProc']] = [];
            }
            $this->_StoredProcParams[$row['StoredProc']][] = new MSSQL_StoredProcParam($row);
        }

        Log::Insert('Got Stored Procs', true);
        return isset($this->_StoredProcParams[$stored_proc]) ? $this->_StoredProcParams[$stored_proc] : [];
    }
}