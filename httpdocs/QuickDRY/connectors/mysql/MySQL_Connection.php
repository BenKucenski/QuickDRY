<?php
/** DO NOT USE THIS CLASS DIRECTLY **/

/**
 * Class MySQL
 */
class MySQL_Connection
{
    public static $use_log = false;
    public static $log_queries_to_file = false;
    public static $keep_files = false;
    public static $log = [];

    protected $db_conns = [];
    protected $db = null;
    protected $current_db = null;
    protected $DB_HOST;
    protected $DB_USER;
    protected $DB_PASS;
    protected $DB_PORT;

    public function __construct($host, $user, $pass, $port = null)
    {
        $this->DB_HOST = $host;
        $this->DB_USER = $user;
        $this->DB_PASS = $pass;
        $this->DB_PORT = $port ? $port : 3306;
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
     * @param $db_base
     *
     * @return bool
     */
    public function CheckDatabase($db_base)
    {
        $sql = 'SELECT SCHEMA_NAME FROM INFORMATION_SCHEMA.SCHEMATA WHERE SCHEMA_NAME = {{}}';
        $res = $this->Query($sql, [$db_base]);

        return isset($res['data'][0]['SCHEMA_NAME']);
    }

    /**
     * @param $db_base
     */
    public function SetDatabase($db_base)
    {
        if(!$db_base) {
            $db_base = $this->current_db;
        }

        if($this->db && !mysqli_ping($this->db)) {
            $this->db_conns[$this->current_db] = null;
            $this->current_db = null;
        }

        if ($db_base && strcmp($this->current_db, $db_base) == 0) {
            return;
        }

        if (!isset($this->db_conns[$db_base]) || is_null($this->db_conns[$db_base])) {
            try {
                $this->db_conns[$db_base] = mysqli_connect(
                    $this->DB_HOST,
                    $this->DB_USER,
                    $this->DB_PASS,
                    $db_base,
                    $this->DB_PORT
                );
                if(!$this->db_conns[$db_base]) {
                    Halt(['Could not connect', $this->DB_HOST, $this->DB_USER]);
                }

            }
            catch(\Exception $e) {
                die('Connect Error (' . mysqli_connect_errno() . ') ' . mysqli_connect_error() . PHP_EOL . print_r([$e, $this->DB_HOST, $this->DB_USER, md5($this->DB_PASS), $db_base], true));
            }
        }

        $this->db = $this->db_conns[$db_base];
        $this->current_db = $db_base;
    }

    /**
     * @param      $sql
     * @param int $time
     * @param null $err
     */
    private function Log($sql, $time = 0, $err = null)
    {
        if (is_null($err)) {
            $err = mysqli_error($this->db);
        }

        if ($err && defined('MYSQL_EXIT_ON_ERROR') && MYSQL_EXIT_ON_ERROR) {
            Halt($err);
        }

        //$this->log[] = $sql;
        if (!static::$use_log) {
            return;
        }

        static::$log[] = ['query' => $sql, 'time'=>$time];

    }

    /**
     * @param      $sql
     * @param null $params
     *
     * @return array
     */
    public function Execute($sql, $params = null, $large = false)
    {
        $query_hash = 'all';
        if(self::$log_queries_to_file) { // don't log as a single array because it makes the queries unreadable
            $query_hash = md5($sql);
            Log::Insert($query_hash);
            Log::Insert($sql);
            Log::Insert($params);
        }

        Metrics::Start('MySQL: ' . $query_hash);

        try {
            $this->_connect();

			if(!$this->db) {
				Halt([$sql, $params, 'mysql went away']);
			}

            $start = microtime(true);

            if ($params) {
                $sql = MySQL::EscapeQuery($this->db, $sql, $params);
            }

            $exec = '';
            $last_id = 0;
            $aff = 0;

            if($large || strlen($sql) > 128 * 1024) {
                if(!is_dir('sql')) {
                    mkdir('sql');
                }
                $fname = 'sql/' . time() . rand(0, 1000000) . '.mysql.txt';
                $fp = fopen($fname, 'w');
                $tries = 0;
                while(!$fp && $tries < 3) {
                    sleep(1);
                    $fname = 'sql/' . time() . rand(0, 1000000) . '.mysql.txt';
                    $fp = fopen($fname, 'w');
                    $tries++;
                }
                if($fp) {
                    fwrite($fp, $sql);
                    fclose($fp);
                } else {
                    Halt('error writing mysql file');
                }

                $file = GUID . '.mysql_config.cnf';
                $fp = fopen($file, 'w');
                fwrite(
                    $fp, '
[client]
user = ' . MYSQLA_USER . '
password = ' . MYSQLA_PASS . '
host = ' . MYSQLA_HOST . '
            '
                );
                fclose($fp);

                $output = [];
                // -x turns off variable interpretation - must be set
                $cmd = 'mysql --defaults-extra-file="' . $file . '" -P' . MYSQLA_PORT . ' < ' . $fname;
                $res = exec($cmd, $output);

                $exec = $cmd . PHP_EOL . PHP_EOL . $res . PHP_EOL . PHP_EOL . implode(PHP_EOL, $output);
                $error = '';

                if(!static::$keep_files) {
                    unlink($fname);
                }

                Metrics::Stop('MySQL: ' . $query_hash);
            } else {

                mysqli_begin_transaction($this->db);
                $res = mysqli_multi_query($this->db, $sql);
                if ($res) {
                    do {
                        /* store first result set */
                        if ($result = mysqli_store_result($this->db)) {
                            mysqli_free_result($result);
                        }
                        $aff += mysqli_affected_rows($this->db);
                    } while (mysqli_more_results($this->db)
                        && mysqli_next_result($this->db));
                }
                $last_id = $this->LastID();

                Metrics::Stop('MySQL: ' . $query_hash);

                $this->Log($sql, microtime(true) - $start);

                $error = mysqli_error($this->db);
                if ($error) {
                    mysqli_rollback($this->db);
                } else {
                    mysqli_commit($this->db);
                }
            }
            return [
                'error' => $error,
                'sql' => $sql,
                'last_id' => $last_id,
                'affected_rows' => $aff,
                'exec' => $exec,
            ];
        } catch (Exception $e) {
            Halt($e);
        }
        return null;
    }

    /**
     * @param      $sql
     * @param null $params
     * @param null $return_type
     * @param bool $objects_only
     *
     * @return array
     */
    public function Query($sql, $params = null, $return_type = null, $map_function =  null)
    {
        if($map_function) {
            Halt($map_function);
        }

        $query_hash = 'all';
        if(self::$log_queries_to_file) { // don't log as a single array because it makes the queries unreadable
            $query_hash = md5($sql);

            Log::Insert($query_hash);
            Log::Insert($sql);
            Log::Insert($params);
        }

        Metrics::Start('MySQL: ' . $query_hash);

        $this->_connect();

        $return = [
            'error' => '',
            'data' => ''
        ];

        if ($params) {
            $sql = MySQL::EscapeQuery($this->db, $sql, $params);
        }

        $start = microtime(true);
        $list = [];
        $res = false;
        if(defined('QUERY_RETRY')) {
            $count = 0;
            while(!$res && $count <= QUERY_RETRY) {
                $res = @mysqli_query($this->db, $sql, MYSQLI_USE_RESULT);
                if(!$res) {
                    if(mysqli_error($this->db)) {
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
            $res = @mysqli_query($this->db, $sql, MYSQLI_USE_RESULT);
        }

        if ($res && is_object($res)) {
            while ($r = mysqli_fetch_assoc($res)) {
                if (is_null($return_type)) {

                    $list[] = !is_null($map_function) ? call_user_func($map_function, $r) : $r;
                } else {
                    if (!class_exists($return_type)) {
                        Halt($return_type . ' does not exist');
                    }

                    $c = new $return_type();
                    $c->FromRow($r);
                    $list[] = $c;
                }
            }

            mysqli_free_result($res);

            do {
                /* store first result set */
                if ($result = mysqli_store_result($this->db)) {
                    mysqli_free_result($result);
                }
            } while (mysqli_more_results($this->db)
                && mysqli_next_result(
                    $this->db
                ));
        }

        $return['error'] = mysqli_error($this->db);
        $return['sql'] = $sql;
        if (is_null($return_type)) {
            $return['data'] = $list;
        } else {
            $return[$return_type] = $list;
        }

        Metrics::Stop('MySQL: ' . $query_hash);

        $this->Log($sql, microtime(true) - $start, $return['error']);


        if ($return_type && !$return['error']) {
            return $return[$return_type];
        }

        return $return;
    }

    /**
     * @param      $values
     * @param bool $quotes
     *
     * @return array|int|string
     */
    public function Escape($values, $quotes = true)
    {
        $this->_connect();
        if (is_array($values)) {
            foreach ($values as $key => $value) {
                $values[$key] = MySQL_A::Escape($value, $quotes);
            }
        } else if ($values === null) {
            $values = 'NULL';
        } else if (is_bool($values)) {
            $values = $values ? 1 : 0;
        } else if (strlen($values) != strlen($values * 1.0) || !is_numeric($values) || $values[0] == '0') {
            $values = mysqli_real_escape_string($this->db, $values);
            if ($quotes) {
                $values = '"' . $values . '"';
            }
        } else if ($quotes) {
            $values = '"' . $values . '"';
        }

        return $values;
    }

    /**
     * @return int|string
     */
    public function LastID()
    {
        $res = mysqli_insert_id($this->db);

        return $res;
    }

    /**
     * @return array
     */
    public function GetTables()
    {
        $sql = '
			SHOW TABLES;
		';
        $res = $this->Query($sql);
        $tables = [];

        foreach ($res['data'] as $d) {
            $tables[$d['Tables_in_' . $this->current_db]] = $d['Tables_in_' . $this->current_db];
        }

        return $tables;
    }

    public function GetTableColumns($table)
    {
        $sql = '
			SHOW COLUMNS FROM
			    `{{nq}}`;
		';
        $res = $this->Query($sql, [$table]);
        if($res['error']) {
            Halt($res);
        }

        $list = [];
        foreach($res['data'] as $row) {
            $l = new MySQL_TableColumn();
            $l->FromRow($row);
            $list[] = $l;
        }
        return $list;
    }

    private static $_LinkedTables = null;

    public function GetLinkedTables($table_name)
    {
        if (!isset(self::$_LinkedTables[$this->current_db])) {
            $sql = '
		SELECT
				table_name AS referenced_table_name,
				column_name AS referenced_column_name,
				referenced_table_name AS table_name,
				referenced_column_name AS column_name,
				CONSTRAINT_NAME
		FROM
				info_schema.key_column_usage
		WHERE
				referenced_table_schema = \'' . $this->current_db . '\'
		  		AND referenced_table_name IS NOT NULL
		ORDER BY column_name

		';
            $res = $this->Query($sql);

            /* @var $fk MSSQL_ForeignKey */
            if (isset($res['data'])) {
                foreach ($res['data'] as $row) {
                    if (!isset($fks[$row['CONSTRAINT_NAME']])) {
                        $fk = new MSSQL_ForeignKey();
                        $fk->FromRow($row);
                    } else {
                        $fk = self::$_LinkedTables[$this->current_db][$row['table_name']][$row['CONSTRAINT_NAME']];
                        $fk->AddRow($row);
                    }

                    if (!isset(self::$_LinkedTables[$this->current_db][$row['table_name']])) {
                        self::$_LinkedTables[$this->current_db][$row['table_name']] = [];
                    }
                    self::$_LinkedTables[$this->current_db][$row['table_name']][$row['CONSTRAINT_NAME']] = $fk;
                }
            }
        }
        if (!isset(self::$_LinkedTables[$this->current_db][$table_name])) {
            self::$_LinkedTables[$this->current_db][$table_name] = [];
        }

        return self::$_LinkedTables[$this->current_db][$table_name];
    }


    private static $_ForeignKeys = null;

    public function GetForeignKeys($table_name)
    {
        if (!isset(self::$_ForeignKeys[$this->current_db])) {


            $sql = '
		SELECT
				table_name,
				column_name,
				referenced_table_name,
				referenced_column_name,
				CONSTRAINT_NAME
		FROM
				info_schema.key_column_usage
		WHERE
				referenced_table_schema = \'' . $this->current_db . '\'
		  		AND referenced_table_name IS NOT NULL
		ORDER BY column_name

		';
            $res = $this->Query($sql);
            self::$_ForeignKeys[$this->current_db] = [];
            foreach ($res['data'] as $row) {
                if (!isset(self::$_ForeignKeys[$this->current_db][$row['table_name']])) {
                    self::$_ForeignKeys[$this->current_db][$row['table_name']] = [];
                }

                /* @var $fk MySQL_ForeignKey */
                if (!isset(self::$_ForeignKeys[$this->current_db][$row['table_name']][$row['CONSTRAINT_NAME']])) {
                    $fk = new MySQL_ForeignKey();
                    $fk->FromRow($row);
                } else {
                    $fk = self::$_ForeignKeys[$this->current_db][$row['table_name']][$row['CONSTRAINT_NAME']];
                    $fk->AddRow($row);
                }

                self::$_ForeignKeys[$this->current_db][$row['table_name']][$row['CONSTRAINT_NAME']] = $fk;
            }
        }
        if (!isset(self::$_ForeignKeys[$this->current_db][$table_name])) {
            self::$_ForeignKeys[$this->current_db][$table_name] = [];
        }

        return self::$_ForeignKeys[$this->current_db][$table_name];
    }

    private static $_PrimaryKey = null;

    /**
     * @param $table_name
     *
     * @return null
     */

    public function GetPrimaryKey($table_name)
    {
        if(is_null(self::$_PrimaryKey) || !isset(self::$_PrimaryKey[$table_name])) {

            $sql = '
SHOW INDEXES FROM
`'  . $table_name . '`

        ';

            $res = MySQL_A::Query($sql);
            if($res['error']) {
                Halt($res);
            }
            foreach($res['data'] as $row) {
                if(!$row['Non_unique'] && $row['Key_name'] === 'PRIMARY') {
                    if(!isset(self::$_PrimaryKey[$row['Table']][$row['Key_name']])) {
                        self::$_PrimaryKey[$row['Table']] = [];
                    }
                    self::$_PrimaryKey[$row['Table']][] = $row['Column_name'];
                }
            }
        }
        if(!isset(self::$_PrimaryKey[$table_name])) {
            self::$_PrimaryKey[$table_name] = [];
        }
        return self::$_PrimaryKey[$table_name];
    }

    private static $_UniqueKeys = null;

    /**
     * @param $table_name
     *
     * @return null
     */

    public function GetUniqueKeys($table_name)
    {
        if(is_null(self::$_UniqueKeys) || !isset(self::$_UniqueKeys[$table_name])) {

            $sql = '
SHOW INDEXES FROM
`'  . $table_name . '`

        ';

            $res = MySQL_A::Query($sql);
            if($res['error']) {
                Halt($res);
            }
            foreach($res['data'] as $row) {
                if(!$row['Non_unique'] && $row['Key_name'] !== 'PRIMARY') {
                    if(!isset(self::$_UniqueKeys[$row['Table']][$row['Key_name']])) {
                        self::$_UniqueKeys[$row['Table']][$row['Key_name']] = [];
                    }
                    self::$_UniqueKeys[$row['Table']][$row['Key_name']][] = $row['Column_name'];
                }
            }
        }
        if(!isset(self::$_UniqueKeys[$table_name])) {
            self::$_UniqueKeys[$table_name] = [];
        }
        return self::$_UniqueKeys[$table_name];
    }

    public function GetStoredProcs()
    {
        $sql = '
			SHOW PROCEDURE STATUS;
		';
        $res = $this->Query($sql);
        if($res['error'] || !sizeof($res['data'])) {
            Halt($res);
        }
        return null;
    }

    public function CopyInfoSchema()
    {
        $this->SetDatabase('INFORMATION_SCHEMA');
        $this->Execute("DROP DATABASE IF EXISTS `info_schema`;",null, true);
        $this->Execute("CREATE DATABASE  `info_schema` ;       ",null, true);
        $this->Execute("CREATE TABLE info_schema.key_column_usage LIKE INFORMATION_SCHEMA.key_column_usage;",null, true);
        $this->Execute("ALTER TABLE info_schema.key_column_usage ENGINE = INNODB;",null, true);
        $this->Execute("ALTER TABLE info_schema.key_column_usage ADD INDEX (`referenced_table_schema`);",null, true);
        $this->Execute("ALTER TABLE info_schema.key_column_usage ADD INDEX (`referenced_table_name`);",null, true);
        $this->Execute("ALTER TABLE info_schema.key_column_usage ADD INDEX (`referenced_column_name`);",null, true);
        $this->Execute("ALTER TABLE info_schema.key_column_usage ADD INDEX (`table_schema`);",null, true);
        $this->Execute("ALTER TABLE info_schema.key_column_usage ADD INDEX (`table_name`);",null, true);
        $this->Execute("ALTER TABLE info_schema.key_column_usage ADD INDEX (`column_name`);",null, true);
        $this->Execute("INSERT INTO info_schema.key_column_usage SELECT * FROM INFORMATION_SCHEMA.key_column_usage;",null, true);
    }
}