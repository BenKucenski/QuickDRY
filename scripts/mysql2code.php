<?php

$_HOST = isset($argv[3]) ? $argv[3] : null;

include '../httpdocs/localsettings.php';
include '../httpdocs/init.php';

MySQL_Connection::$use_log = false; // must turn off logging or it screws things up since we're not working with classes

$database = isset($argv[1]) ? $argv[1] : '';

if(!$database)
	exit;

$get_schema = isset($argv[2]) ? $argv[2] : 1;

if($get_schema)
{
	if(file_exists('nav.txt')) unlink('nav.txt');
	MySQL_A::SetDatabase(MYSQLA_BASE);
	MySQL_A::Execute("DROP DATABASE IF EXISTS `info_schema`;",null, true);
	MySQL_A::Execute("CREATE DATABASE  `info_schema` ;       ",null, true);
	MySQL_A::Execute("CREATE TABLE info_schema.key_column_usage LIKE INFORMATION_SCHEMA.key_column_usage;",null, true);
	MySQL_A::Execute("ALTER TABLE info_schema.key_column_usage ENGINE = INNODB;",null, true);
	MySQL_A::Execute("ALTER TABLE info_schema.key_column_usage ADD INDEX (`referenced_table_schema`);",null, true);
	MySQL_A::Execute("ALTER TABLE info_schema.key_column_usage ADD INDEX (`referenced_table_name`);",null, true);
	MySQL_A::Execute("ALTER TABLE info_schema.key_column_usage ADD INDEX (`referenced_column_name`);",null, true);
	MySQL_A::Execute("ALTER TABLE info_schema.key_column_usage ADD INDEX (`table_schema`);",null, true);
	MySQL_A::Execute("ALTER TABLE info_schema.key_column_usage ADD INDEX (`table_name`);",null, true);
	MySQL_A::Execute("ALTER TABLE info_schema.key_column_usage ADD INDEX (`column_name`);",null, true);
	MySQL_A::Execute("INSERT INTO info_schema.key_column_usage SELECT * FROM INFORMATION_SCHEMA.key_column_usage;",null, true);
}



if(!is_dir('includes'))
	mkdir('includes');

if(!is_dir('class'))
	mkdir('class');
if(!is_dir('class/'.$database))
	mkdir('class/'.$database);

if(!is_dir('db'))
	mkdir('db');
if(!is_dir('db/'.$database))
	mkdir('db/'.$database);
if(!is_dir('db/'.$database . '/db'))
	mkdir('db/'.$database . '/db');

if(!is_dir('phpunit'))
	mkdir('phpunit');

MySQL_A::SetDatabase($database);

$sql = 'SHOW PROCEDURE STATUS WHERE Db = \'' . $database . '\' AND Type = \'PROCEDURE\'';
$stored_procs = MySQL_A::Query($sql);

if(sizeof($stored_procs['data'])) {
    Debug($stored_procs);
}


$sql = 'show full tables where Table_Type != \'VIEW\'';
$tables = MySQL_A::query($sql);
$modules = array();

foreach($tables['data'] as $table)
{
	$table_name = $table['Tables_in_' . $database];
	echo "$table_name\r\n";

	$sql = '
		SHOW INDEXES FROM
			`' . $table_name . '`
	';
	$res = MySQL_A::query($sql);
	$unique = array();
	foreach($res['data'] as $row)
		if($row['Non_unique'] == 0 && strcmp($row['Key_name'],'PRIMARY') != 0)
			$unique[] = $row['Column_name'];

	$sql = '
		SHOW COLUMNS FROM
			`' . $table_name . '`
	';
	$columns = MySQL_A::query($sql);
	$sql = 'SHOW TRIGGERS IN ' . $database . ' LIKE "' . $table_name . '"';
	$triggers = MySQL_A::query($sql);


	$cols = array();
	$mod = GenerateClass($table_name, $columns['data'], $unique, $triggers['data']);
	$modules[] = 'require_once \'common/' . $database . '/db/db_' . $mod . '.php\';';
	$modules[] = 'require_once \'common/' . $database . '/' . $mod . '.php\';';
}

$fp = fopen('includes/my_' . $database . '.php','w');
fwrite($fp,'<?php' . "\r\n" . implode("\r\n",$modules));
fclose($fp);

function GenerateClass($table_name, $cols, $unique, $triggers)
{
	global $database;

	$is_base = substr($database,-5) === '_base';

	$c_name = SQL_Base::TableToClass($database, $table_name);

	$class_props = array();
	$column_names = array();
	$count_properties = array();

	$can_delete_adds = array();

	$gets = array();
	$refs = array();
	$ref_funcs = array();
	$props = '';
	$primary = array();


	foreach($cols as $col)
	{
		$sql = "
			SELECT
					table_name,
					column_name,
					referenced_table_name,
					referenced_column_name
			FROM
					info_schema.key_column_usage
			WHERE
					referenced_table_schema = '" . $database . "'
					AND referenced_table_name = '" . $table_name . "'
					AND referenced_column_name = '" . $col['Field'] . "'
			ORDER BY table_name
		";
		$res = MySQL_A::query($sql);

		if(sizeof($res['data']))
		{
			foreach($res['data'] as $row)
			{
				$prop_name = str_replace('_id','',$row['column_name']);

				$prop_name_count = $row['table_name'] . '_' . $prop_name . '_count';
				$prop_name = $row['table_name'] . '_' . $prop_name;

				$count_properties[] = 'private $_' . $prop_name_count . ' = null;';
				$class_props[] = ' * @property integer ' . $prop_name_count;
				$count_properties[] = 'private $_' . $prop_name . ' = null;';
				$class_props[] = ' * @property ' . SQL_Base::TableToClass($database, $row['table_name']) . '[] ' . $prop_name;
				$can_delete_adds[] = '$this->' . $prop_name_count;

			$gets[] = "
			case '{$prop_name_count}':
				if(is_null(\$this->_{$prop_name_count}))
					\$this->_{$prop_name_count} = " . SQL_Base::TableToClass($database, $row['table_name']) . "::GetCount(['" . $row['column_name'] . "'=>\$this->" . $row['referenced_column_name'] . "]);
				return \$this->_{$prop_name_count};

			case '{$prop_name}':
				if(is_null(\$this->_{$prop_name}))
					\$this->_{$prop_name} = " . SQL_Base::TableToClass($database, $row['table_name']) . "::GetAll(['" . $row['column_name'] . "'=>\$this->" . $row['referenced_column_name'] . "]);
				return \$this->_{$prop_name};
				";
			}
		}

		$class_props[] = ' * @property ' . ColumnTypeToProperty(preg_replace('/\(.*?\)/si','',$col['Type'])) . ' ' . $col['Field'];
		$column_names[] = $col['Field'];

		if(strcasecmp($col['Key'],'pri') == 0)
		{
			$primary[] = $col['Field'];
			$props .= "'" . $col['Field'] . "'=>array('display'=>'" . $col['Field'] . "', 'type'=>'" . str_replace("'",'"',$col['Type']) . "', 'nullable'=>" . (strcasecmp($col['Null'],'no')==0 ? 'false' : 'true') . "),\r\n\t\t";
		}
		else
			$props .= "'" . $col['Field'] . "'=>array('display'=>'" . FieldToDisplay($col['Field']) . "', 'type'=>'" . str_replace("'",'"',$col['Type']) . "', 'nullable'=>" . (strcasecmp($col['Null'],'no')==0 ? 'false' : 'true') . "),\r\n\t\t";
	}

	$sql = "
		SELECT
				table_name,
				column_name,
				referenced_table_name,
				referenced_column_name
		FROM
				info_schema.key_column_usage
		WHERE
				referenced_table_schema = '" . $database . "'
				AND table_name = '" . $table_name . "'
		  		AND referenced_table_name IS NOT NULL
		ORDER BY column_name
	";

	$res = MySQL_A::query($sql);


	foreach($res['data'] as $fk)
	{
		// remove "_id"
		$var = substr($fk['column_name'],0,strlen($fk['column_name']) - 3);
		$ref_class = SQL_Base::TableToClass($database, $fk['referenced_table_name']);

		$class_props[] = ' * @property ' . $ref_class . ' ' . $var;

		$refs[] = 'protected $_' . $var . ' = null;';
		$ref_funcs[] = '
	public function Set' . str_replace(' ', '', ucwords(str_replace('_',' ',$var))) . '(' . $ref_class . ' $' . $var . ') {
		$this->_' . $var .  ' = $' . $var . ';
	}
		';
		$gets[] = "
			case '$var':
				if(is_null(\$this->_$var))
					\$this->_$var = " . SQL_Base::TableToClass($database, $fk['referenced_table_name']) . "::Get(['" . $fk['referenced_column_name'] . "'=>\$this->" . $fk['column_name'] . "]);
				return \$this->_$var;
			";
	}

$count_properties = implode("\r\n\t", $count_properties);

$code = '<?php
/**
 *
 * ' . $c_name . '
 * @author Ben Kucenski <bkucenski@gmail.com>
 * generated by QuickDRY
 *
';
$code .= implode("\r\n",$class_props);
$code .= '
 *
 */

class db_' . $c_name . ' extends MySQL_A
{
	public static $_primary = [\'' . implode('\',\'',$primary) . '\'];
	public static $_unique = [' .  (sizeof($unique) ? '\'' . implode('\',\'',$unique) . '\'' : '') . '];

	protected static $database = \'' . $database . '\';
	protected static $table = \'' . $table_name . '\';

	' . ($is_base ? 'private $_object_database = null;' : '') . '
	' . $count_properties . '

	protected static $prop_definitions = array(
		' . $props . '
	);

	' . implode("\r\n\t",$refs) . '

	' . implode("\r\n\t",$ref_funcs) . '


	' . ($is_base ? '
	public function __set($name, $value)
	{
		switch($name)
		{
			case \'object_database\':
			$this->_object_database = $value;
			return;
		}
	}
	' : '' ) . '

	public function __get($name)
	{
		switch($name)
		{
			' . implode("\r\n\t\t",$gets) . '

			' . ($is_base ? '

			case \'object_database\':
				return $this->_object_database;
			' : '' ) . '

			default:
				return parent::__get($name);
		}
	}

	' . ($is_base ? '
	public static function SetDatabase($database)
	{
		static::$database = $database;
	}
	' : '' ) . '

	public function FromRequest(&$req, $save = true, $overwrite = false)
	{
		return parent::FromRequest($req, $save, $overwrite);
	}

	public static function Suggest($search)
	{
		exit(\'Suggest not implemented\');
	}

	public function IsReferenced()
	{
		return ' . (sizeof($can_delete_adds) ? implode(' + ', $can_delete_adds) . ' == 0 ? 1 : 0' : '1') . ';
	}

	public function VisibleTo(SecurityTitleUserClass &$user)
	{
		if($user->Is([USER_ROLE_ADMIN]))
			return true;

		return false;
	}

	public function CanDelete(SecurityTitleUserClass &$user)
	{
		if($user->Is([USER_ROLE_ADMIN]))
			return true;

		return false;
	}

	public static function ColumnNameToNiceName($column_name)
	{
		return isset(static::$prop_definitions[$column_name]) ? static::$prop_definitions[$column_name][\'display\'] : \'<i>unknown</i>\';
	}

	public function ValueToNiceValue($column_name, $value)
	{
		return $value;
	}

	public function IgnoreColumn($column_name)
	{
		return in_array($column_name, array(\'id\', \'created_at\', \'created_by_id\', \'edited_at\', \'edited_by_id\'));
	}

    /**
     * @param $where
     *
     * @return ' . $c_name . '
     */
    public static function Get($where)
    {
        return parent::Get($where);
    }

    /**
     * @param null $where
     * @param null $order_by
     * @param null $limit
     *
     * @return ' . $c_name . '[]|null
     */
    public static function GetAll($where = null, $order_by = null, $limit = null)
    {
        return parent::GetAll($where, $order_by, $limit);
    }

';

    foreach($triggers as $trigger) {
        $code .= '

    public function trigger_' . $trigger['Trigger'] . '()
    {
        /*
            EVENT:      ' . $trigger['Event'] . '
            TIMING:     ' . $trigger['Timing'] . '
            DEFINER:    ' . $trigger['Definer'] . '
        */

        $sql = \'
        ' . str_replace("'", "\\'", $trigger['Statement']) . '
        \';
        return $sql;
    }

        ';
    }

$code .= '
}
';
	$fp = fopen('db/' . $database . '/db/db_' . $c_name . '.php','w');
	fwrite($fp, $code);
	fclose($fp);

$code = '<?php
/**
 *
 * @author Ben Kucenski <bkucenski@gmail.com>
 * generated by QuickDRY
 *
 */

class ' . $c_name . ' extends db_' . $c_name . '
{
	public function __construct()
	{
		parent::__construct();
	}

	public function __get($name)
	{
		switch($name)
		{
			default:
				return parent::__get($name);
		}
	}

	public function Save()
	{
		return $this->_Save();
	}

	public function FromRequest(&$req, $save = true, $overwrite = false)
	{
		return parent::FromRequest($req, $save, $overwrite);
	}
}

';

	$fp = fopen('class/' . $database . '/' . $c_name . '.php','w');
	fwrite($fp, $code);
	fclose($fp);


$code = '<?php
/**
 *
 * @author Ben Kucenski <bkucenski@gmail.com>
 * generated by QuickDRY
 *
 */

class ' . $c_name . 'Test extends PHPUnit_Framework_TestCase
{
}

';

	$fp = fopen('phpunit/' . $c_name . 'Test.php','w');
	fwrite($fp, $code);
	fclose($fp);

	return $c_name;
}