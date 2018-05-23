<?php

/**
 * Class SQLCodeGen
 */
class SQLCodeGen extends SafeClass
{
    protected $DestinationFolder;
    protected $Database;
    protected $DatabaseConstant;
    protected $DatabasePrefix;
    protected $UserClass;
    protected $UserVar;
    protected $UserIdColumn;
    protected $MasterPage;
    protected $Tables;
    protected $LowerCaseTables;
    protected $UseFKColumnName;
    protected $DatabaseTypePrefix;
    protected $DatabaseClass;
    protected $GenerateJSON;

    protected $IncludeFolder;
    protected $CommonFolder;
    protected $CommonClassFolder;
    protected $CommonClassDBFolder;
    protected $CommonClassSPFolder;
    protected $CommonClassSPDBFolder;
    protected $PagesBaseJSONFolder;
    protected $PagesJSONFolder;
    protected $PagesJSONControlsFolder;

    protected $PagesBaseManageFolder;
    protected $PagesManageFolder;
    protected $PagesPHPUnitFolder;
    
    protected function CreateDirectories()
    {
        $this->IncludeFolder = $this->DestinationFolder . '/includes';

        $this->CommonFolder = $this->DestinationFolder . '/common';
        $this->CommonClassFolder = $this->DestinationFolder . '/common/' . $this->DatabaseTypePrefix . '_' . strtolower($this->DatabasePrefix);
        $this->CommonClassDBFolder = $this->DestinationFolder . '/common/' . $this->DatabaseTypePrefix . '_' . strtolower($this->DatabasePrefix) . '/db';

        $this->CommonClassSPFolder = $this->DestinationFolder . '/common/' . $this->DatabaseTypePrefix . '_' . strtolower($this->DatabasePrefix) . '/sp';
        $this->CommonClassSPDBFolder = $this->DestinationFolder . '/common/' . $this->DatabaseTypePrefix . '_' . strtolower($this->DatabasePrefix) . '/sp_db';

        $this->PagesPHPUnitFolder = $this->DestinationFolder . '/phpunit';

        $this->PagesBaseJSONFolder = $this->DestinationFolder . '/pages/json';
        $this->PagesBaseManageFolder = $this->DestinationFolder . '/pages/manage';

        if(!is_dir($this->PagesBaseJSONFolder)) {
            mkdir($this->PagesBaseJSONFolder);
        }

        if(!is_dir($this->PagesBaseManageFolder)) {
            mkdir($this->PagesBaseManageFolder);
        }

        if (!is_dir($this->IncludeFolder)) {
            mkdir($this->IncludeFolder);
        }

        if (!is_dir($this->CommonFolder)) {
            mkdir($this->CommonFolder);
        }

        if (!is_dir($this->CommonClassFolder)) {
            mkdir($this->CommonClassFolder);
        }

        if (!is_dir($this->CommonClassDBFolder)) {
            mkdir($this->CommonClassDBFolder);
        }

        if (!is_dir($this->CommonClassSPFolder)) {
            mkdir($this->CommonClassSPFolder);
        }

        if (!is_dir($this->CommonClassSPDBFolder)) {
            mkdir($this->CommonClassSPDBFolder);
        }

        if (!is_dir($this->PagesPHPUnitFolder)) {
            mkdir($this->PagesPHPUnitFolder);
        }
    }

    /**
     * @param $col_type
     *
     * @return string
     */
    public static function ColumnTypeToProperty($col_type)
    {
        switch (strtolower($col_type)) {
            case 'varchar':
            case 'char':
            case 'keyword':
            case 'text':
            case 'nvarchar':
                return 'string';

            case 'tinyint unsigned':
            case 'bigint unsigned':
            case 'int unsigned':
                return 'uint';

            case 'numeric':
            case 'tinyint':
            case 'smallint':
            case 'bit':
                return 'int';

            case 'money':
            case 'decimal':
                return 'float';

            case 'smalldatetime':
                return 'datetime';
        }
        return $col_type;
    }

    /**
     * @param $sp_class
     */
    public function GenerateSPClassFile($sp_class)
    {
        $code = '<?php

/**
 * Class ' . $sp_class . '
 */
class ' . $sp_class . ' extends db_' . $sp_class . '
{
    /**
     * ' . $sp_class . ' constructor.
     * @param null $row
     */
    public function __construct($row = null)
    {
        if($row) {
            $this->HaltOnError(false);
            $this->FromRow($row);
            if($this->HasMissingProperties()) {
                Halt($this->GetMissingPropeties());
            }
            $this->HaltOnError(true);
        }
    }
}
';
        $file = $this->CommonClassSPFolder . '/' . $sp_class . '.php';
        if(!file_exists($file)) { // do not overwrite existing files, user edited
            $fp = fopen($file, 'w');
            fwrite($fp, $code);
            fclose($fp);
        }
    }

    public function GenerateClasses()
    {
        $modules = $this->GenerateDatabaseClass();

        foreach ($this->Tables as $table_name) {
            Log::Insert($table_name, true);


            $DatabaseClass = $this->DatabaseClass;
            $columns = $DatabaseClass::GetTableColumns($table_name);
            $mod = $this->GenerateClass($table_name, $columns);

            $modules['db_' . $mod] = str_replace($this->DestinationFolder . '/', '', $this->CommonClassDBFolder . '/db_' . $mod . '.php');
            $modules[$mod] = str_replace($this->DestinationFolder . '/', '', $this->CommonClassFolder . '/' . $mod . '.php');
        }

        $fp = fopen($this->IncludeFolder . '/' . $this->DatabaseTypePrefix . '_' . strtolower($this->DatabasePrefix) . '.php', 'w');

        $mod_map = [];
        foreach ($modules as $mod => $file) {
            $mod_map[] = '\'' . $mod . '\' => \'' . $file . '\',';
        }
        $autoloader_class = 'autoloader_' . md5($this->DatabaseTypePrefix . '_' . $this->DatabasePrefix);

        $include_php = '<?php
/**
 * @param $class
 */
function ' . $autoloader_class . '($class) {
    $class_map = [
        ' . implode("\r\n\t\t", $mod_map) . '
    ];

    if(!isset($class_map[$class])) {
        return;
    }

    if (file_exists($class_map[$class])) { // web
        require_once $class_map[$class];
    } else {
        if (file_exists(\'../\' . $class_map[$class])) { // cron folder
            require_once \'../\' . $class_map[$class];
        } else { // scripts folder
           require_once \''  . $this->DestinationFolder . '/\' . $class_map[$class];
        }
    }
}


spl_autoload_register(\'' . $autoloader_class . '\');
        ';

        fwrite($fp, $include_php);
        fclose($fp);
    }

    /**
     * @param $table_name
     * @param $cols
     * @return string
     */
    function GenerateClass($table_name, $cols)
    {
        $DatabaseClass = $this->DatabaseClass;
        $class_props = [];

        $c_name = SQL_Base::TableToClass($this->DatabasePrefix, $table_name, $this->LowerCaseTables, $this->DatabaseTypePrefix);
        Log::Insert($c_name, true);

        $props = '';
        $unique = $DatabaseClass::GetUniqueKeys($table_name);
        $primary = $DatabaseClass::GetPrimaryKey($table_name);
        $indexes = $DatabaseClass::GetIndexes($table_name);

        $aliases = [];

        foreach ($cols as $col) { /* @var $col MSSQL_TableColumn */ // these are the same for MySQL and MSSQL, only claim it's one to help with code completion
            if($col->field !== $col->field_alias) {
                $aliases[] = $col;
            }
            $class_props[] = ' * @property ' . SQLCodeGen::ColumnTypeToProperty(preg_replace('/\(.*?\)/si', '', $col->type)) . ' ' . $col->field_alias;
            $props .= "'" . $col->field . "'=>['display'=>'" . FieldToDisplay($col->field) . "', 'type'=>'" . str_replace('\'', '\\\'', $col->type) . "', 'is_nullable'=>" . (strcasecmp($col->null, 'no') == 0 ? 'false' : 'true') . "],\r\n\t\t";
        }


        $refs = $DatabaseClass::GetForeignKeys($table_name);
        $gets = [];
        $sets = [];

        $foreign_key_props = [];

        $seens_vars = [];

        foreach($aliases as $alias) { /* @var $alias MSSQL_TableColumn */
            $gets[] = "
            case '" . $alias->field_alias . "':
                return \$this->GetProperty('" . $alias->field . "');
            ";

            $sets[] = "
            case '" . $alias->field_alias . "':
                return \$this->SetProperty('" . $alias->field . "', \$value);
            ";

        }

        foreach ($refs as $fk) {
            if (is_array($fk->column_name)) {
                $column_name = $this->UseFKColumnName ? '_' . implode('_', $fk->column_name) : '';
                $var = preg_replace('/[^a-z0-9]/si', '_', str_replace(' ', '_', $fk->foreign_table_name) . $column_name);
            } else {
                $column_name = $this->UseFKColumnName ? '_' . $fk->column_name : '';
                $var = preg_replace('/[^a-z0-9]/si', '_', str_replace(' ', '_', $fk->foreign_table_name) . $column_name);
            }

            if (in_array($var, $seens_vars)) {
                Log::Insert(['duplicate FK', $fk], true);
                continue;
            }
            $seens_vars[] = $var;

            $class_props[] = ' * @property ' . SQL_Base::TableToClass($this->DatabasePrefix, $fk->foreign_table_name, $this->LowerCaseTables, $this->DatabaseTypePrefix) . ' ' . $var;
            $foreign_key_props[] = 'protected $_' . $var . ' = null;';

            if (is_array($fk->column_name)) {
                $isset = [];
                $get_params = [];
                foreach ($fk->column_name as $i => $col) {
                    $isset[] = '$this->' . $col;
                    $get_params[] = "'" . $fk->foreign_column_name[$i] . "'=>\$this->" . $col;
                }

                $gets[] = "
            case '$var':
                if(!isset(\$this->_$var) && " . implode(' && ', $isset) . ") {
                    \$this->_$var = " . SQL_Base::TableToClass($this->DatabasePrefix, $fk->foreign_table_name, $this->LowerCaseTables, $this->DatabaseTypePrefix) . "::Get([" . implode(', ', $get_params) . "]);
                }
                return \$this->_$var;
            ";
            } else {
                $gets[] = "
            case '$var':
                if(!isset(\$this->_$var) && \$this->" . $fk->column_name . ") {
                    \$this->_$var = " . SQL_Base::TableToClass($this->DatabasePrefix, $fk->foreign_table_name, $this->LowerCaseTables, $this->DatabaseTypePrefix) . "::Get(['" . $fk->foreign_column_name . "'=>\$this->" . $fk->column_name . "]);
                }
                return \$this->_$var;
            ";
            }
        }

        $refs = $DatabaseClass::GetLinkedTables($table_name);
        $fk_counts = [];
        foreach ($refs as $fk) {
            if (is_array($fk->column_name)) {
                $column_name = $this->UseFKColumnName ? '_' . str_ireplace('_ID', '', implode('_', $fk->column_name)) : '';
                $var = preg_replace('/[^a-z0-9]/si', '_', str_replace(' ', '_', $fk->foreign_table_name) . $column_name);
            } else {
                $column_name = $this->UseFKColumnName ? '_' . str_ireplace('_ID', '', $fk->column_name) : '';
                $var = preg_replace('/[^a-z0-9]/si', '_', str_replace(' ', '_', $fk->foreign_table_name) . $column_name);
            }


            if (in_array($var, $seens_vars)) {
                Log::Insert(['duplicate FK', $fk], true);
                continue;
            }
            $seens_vars[] = $var;

            $class_props[] = ' * @property ' . SQL_Base::TableToClass($this->DatabasePrefix, $fk->foreign_table_name, $this->LowerCaseTables, $this->DatabaseTypePrefix) . '[] ' . $var;
            $class_props[] = ' * @property int ' . $var . 'Count';


            $foreign_key_props[] = 'protected $_' . $var . ' = null;';
            $foreign_key_props[] = 'protected $_' . $var . 'Count = null;';

            if (is_array($fk->column_name)) {
                $isset = [];
                $get_params = [];
                foreach ($fk->column_name as $i => $col) {
                    $isset[] = '$this->' . $col;
                    $get_params[] = "'" . $fk->foreign_column_name[$i] . "'=>\$this->" . $col;
                }
                $fk_counts [] = $var . 'Count';

                $gets[] = "
            case '$var':
                if(is_null(\$this->_$var) && " . implode(' && ', $isset) . ") {
                    \$this->_$var = " . SQL_Base::TableToClass($this->DatabasePrefix, $fk->foreign_table_name, $this->LowerCaseTables, $this->DatabaseTypePrefix) . "::GetAll([" . implode(', ', $get_params) . "]);
                }
                return \$this->_$var;

            case '{$var}Count':
                if(is_null(\$this->_{$var}Count) && " . implode(' && ', $isset) . ") {
                    \$this->_{$var}Count = " . SQL_Base::TableToClass($this->DatabasePrefix, $fk->foreign_table_name, $this->LowerCaseTables, $this->DatabaseTypePrefix) . "::GetCount([" . implode(', ', $get_params) . "]);
                }
                return \$this->_{$var}Count;
            ";

            } else {
                $fk_counts [] = $var . 'Count';
                $gets[] = "
            case '$var':
                if(is_null(\$this->_$var) && \$this->" . $fk->column_name . ") {
                    \$this->_$var = " . SQL_Base::TableToClass($this->DatabasePrefix, $fk->foreign_table_name, $this->LowerCaseTables, $this->DatabaseTypePrefix) . "::GetAll(['" . $fk->foreign_column_name . "'=>\$this->" . $fk->column_name . "]);
                }
                return \$this->_$var;

            case '{$var}Count':
                if(is_null(\$this->_{$var}Count) && \$this->" . $fk->column_name . ") {
                    \$this->_{$var}Count = " . SQL_Base::TableToClass($this->DatabasePrefix, $fk->foreign_table_name, $this->LowerCaseTables, $this->DatabaseTypePrefix) . "::GetCount(['" . $fk->foreign_column_name . "'=>\$this->" . $fk->column_name . "]);
                }
                return \$this->_{$var}Count;
            ";
            }
        }


        $code = '<?php

/**
 *
 * ' . $c_name . '
 * @author Ben Kucenski <bkucenski@gmail.com>
 * generated by QuickDRY
 *
';
        $code .= implode("\r\n", $class_props);
        $code .= '
 *
 */

class db_' . $c_name . ' extends ' . $DatabaseClass . '
{
    public static $_primary = [\'' . implode('\',\'', $primary) . '\'];
    public static $_unique = [
    ';

        foreach ($unique as $key => $columns) {
            $code .= '        [' . (sizeof($columns) ? '\'' . implode('\',\'', $columns) . '\'' : '') . '],' . PHP_EOL;
        }


        $code .= '
        ];

    public static $_indexes = [
    ';

        foreach ($indexes as $key => $columns) {
            $code .= '        [' . (sizeof($columns) ? '\'' . implode('\',\'', $columns) . '\'' : '') . '],' . PHP_EOL;
        }


        $code .= '
        ];

    protected static $database = ' . (!$this->DatabaseConstant ? '\'' . $this->Database . '\'' : $this->DatabaseConstant) . ';
    protected static $table = \'' . $table_name . '\';
    protected static $DatabasePrefix = \'' . (!$this->DatabaseConstant ? $this->Database : $this->DatabaseConstant) . '\';
    protected static $DatabaseTypePrefix = \'' . $this->DatabaseTypePrefix . '\';
    protected static $LowerCaseTable = ' . ($this->LowerCaseTables ? 1 : 0) . ';

    protected static $prop_definitions = [
        ' . $props . '
    ];

    ' . implode("\r\n\t", $foreign_key_props) . '

    /**
     * @param $name
     * @return mixed
     */
    public function __get($name)
    {
        switch($name)
        {
            ' . implode("\r\n        ", $gets) . '
            default:
                return parent::__get($name);
        }
    }

    /**
     * @param $name
     * @param $value
     * @return mixed
     */
    public function __set($name, $value)
    {
        switch($name)
        {
            ' . implode("\r\n        ", $sets) . '
            default:
                return parent::__set($name, $value);
        }
    }

    /**
     * @param $req
     * @param bool $save
     * @param bool $overwrite
     * @return bool
     */
    public function FromRequest(&$req, $save = true, $overwrite = false)
    {
        return parent::FromRequest($req, $save, $overwrite);
    }

    /**
     * @param $search
     * @param UserClass $user
     */
    public static function Suggest($search, ' . $this->UserClass . ' &$user)
    {
        HTTP::ExitJSON([\'error\' => \'Suggest not implemented\', \'search\' => $search, \'user\' => $user]);
    }

    /**
     * @return int
     */
    public function IsReferenced()
    {
        return ' . (sizeof($fk_counts) == 0 ? '0' : '$this->' . implode(' + $this->', $fk_counts)) . ';
    }

    /**
     * @param UserClass $user
     * @return bool
     */
    public function VisibleTo(' . $this->UserClass . ' &$user)
    {
        if($user->Is([ROLE_ID_ADMIN])) {
            return true;
        }

        return false;
    }

    /**
     * @param UserClass $user
     * @return bool
     */
    public function CanDelete(' . $this->UserClass . ' &$user)
    {
        if($user->Is([ROLE_ID_ADMIN])) {
            return true;
        }

        return false;
    }

    /**
     * @param $column_name
     * @return string
     */
    public static function ColumnNameToNiceName($column_name)
    {
        return isset(static::$prop_definitions[$column_name]) ? static::$prop_definitions[$column_name][\'display\'] : \'<i>unknown</i>\';
    }

    /**
     * @param $column_name
     * @param null $value
     * @return mixed
     */
    public function ValueToNiceValue($column_name, $value = null)
    {
        if($value instanceof DateTime) {
            return Dates::Timestamp($value, \'\');
        }

        if($this->$column_name instanceof DateTime) {
            return Dates::Timestamp($this->$column_name, \'\');
        }

        return $value ? $value : $this->$column_name;
    }

    /**
     * @param $column_name
     * @return bool
     */
    public static function IgnoreColumn($column_name)
    {
        return in_array($column_name, [\'id\', \'created_at\', \'created_by_id\', \'edited_at\', \'edited_by_id\']);
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
    public static function GetAll($where = null, $order_by = null, $limit = null )
    {
		return parent::GetAll($where, $order_by, $limit);
    }
}
';
        $fp = fopen($this->CommonClassDBFolder . '/db_' . $c_name . '.php', 'w');
        fwrite($fp, $code);
        fclose($fp);

        $code = '<?php
/**
 * Class ' . $c_name . '
 */
class ' . $c_name . ' extends db_' . $c_name . '
{
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * @param $name
     * @return mixed
     */
    public function __get($name)
    {
        switch($name)
        {
            default:
                return parent::__get($name);
        }
    }

    /**
     * @return array
     */
    public function Save()
    {
        return $this->_Save();
    }

    /**
     * @param $req
     * @param bool $save
     * @param bool $overwrite
     * @return bool
     */
    public function FromRequest(&$req, $save = true, $overwrite = false)
    {
        return parent::FromRequest($req, $save, $overwrite);
    }
}

';
        
        $file = $this->CommonClassFolder . '/' . $c_name . '.php';
        if(!file_exists($file)) {
            $fp = fopen($file, 'w');
            fwrite($fp, $code);
            fclose($fp);
        }

        return $c_name;
    }

    public function GenerateJSON()
    {
        if(!$this->GenerateJSON) {
            return;
        }


        $DatabaseClass = $this->DatabaseClass;

        foreach ($this->Tables as $table_name) {
            Log::Insert($table_name, true);
            $c_name = SQL_Base::TableToClass($this->DatabasePrefix, $table_name, $this->LowerCaseTables, $this->DatabaseTypePrefix);

            $this->PagesJSONFolder = $this->PagesBaseJSONFolder . '/' . $c_name;
            if(!is_dir($this->PagesJSONFolder)) {
                mkdir($this->PagesJSONFolder);
            }

            $this->PagesJSONControlsFolder = $this->PagesJSONFolder . '/controls';
            if(!is_dir($this->PagesJSONControlsFolder)) {
                mkdir($this->PagesJSONControlsFolder);
            }

            $this->PagesManageFolder = $this->PagesBaseManageFolder . '/' . str_replace('Class', '', $c_name);
            if(!is_dir($this->PagesManageFolder)) {
                mkdir($this->PagesManageFolder);
            }


            $columns = $DatabaseClass::GetTableColumns($table_name);
            $this->_GenerateJSON($table_name, $columns);
        }
    }

    protected function _GenerateJSON($table_name, $cols)
    {
        $DatabaseClass = $this->DatabaseClass;

        $column_names = [];
        foreach ($cols as $col) {
            $column_names[] = $col->field;
        }

        $c_name = SQL_Base::TableToClass($this->DatabasePrefix, $table_name, $this->LowerCaseTables, $this->DatabaseTypePrefix);

        $unique = $DatabaseClass::GetUniqueKeys($table_name);
        $primary = $DatabaseClass::GetPrimaryKey($table_name);


        $this->SaveJSON($c_name, $primary);
        $this->GetJSON($c_name, $primary);
        $this->LookupJSON($c_name);
        $this->DeleteJSON($c_name, $unique, $primary);
        $this->HistoryJSON($c_name, $primary);
        $this->Add($c_name, $table_name, $cols, $primary);
        $this->History($c_name);
        $this->Manage($c_name);
    }

    protected function SaveJSON($c_name, $primary)
    {
        if (!isset($primary[0])) {
            return;
        }

        $save = '<?php
if(!$Web->' . $this->UserVar . ' || !$Web->' . $this->UserVar . '->' . $this->UserIdColumn . ') {
    HTTP::ExitJSON([\'error\'=>\'Invalid Request\'], HTTP_STATUS_UNAUTHORIZED);
}

$returnvals = [];

if($_SERVER[\'REQUEST_METHOD\'] == \'POST\')
{
	if(isset($_POST[\'serialized\']))
	{
		$req = HTTP::PostFromSerialized($_POST[\'serialized\']);
		$primary = isset(' . $c_name . '::$_primary[0]) ? ' . $c_name . '::$_primary[0] : \'id\';
		if(isset($req[$primary]) && $req[$primary]) {
			$c = ' . $c_name . '::Get([$primary=>$req[$primary]]);
		} else {
			$c = new ' . $c_name . '();
        }

		$c->FromRequest($req, false);
		if(!$c->VisibleTo($Web->' . $this->UserVar . ')) {
			HTTP::ExitJSON([\'error\'=>\'Invalid Request\'], HTTP_STATUS_UNAUTHORIZED);
        }
		$res = $c->FromRequest($req);

		if(!isset($res[\'error\']) || !$res[\'error\'])
		{
			$returnvals[\'success\'] = \'' . CapsToSpaces(str_replace('Class', '', $c_name)) . ' Saved!\';
			$returnvals[$primary] = $c->$primary;
			$returnvals[\'serialized\'] = $c->ToArray();
		} else {
			$returnvals[\'error\'] = $res[\'error\'];
        }
	} else {
		$returnvals[\'error\'] = \'' . CapsToSpaces(str_replace('Class', '', $c_name)) . ' - Save: Bad params passed in!\';
    }
} else {
	$returnvals[\'error\'] = \'' . CapsToSpaces(str_replace('Class', '', $c_name)) . ' - Save: Bad Request sent!\';
}

HTTP::ExitJSON($returnvals);
	';
        $fp = fopen($this->PagesJSONFolder . '/save.json.php', 'w');
        fwrite($fp, $save);
        fclose($fp);
    }

    protected function GetJSON($c_name, $primary)
    {
        if (!isset($primary[0])) {
            return;
        }

        $get = '<?php
if(!$Web->' . $this->UserVar . ' || !$Web->' . $this->UserVar . '->' . $this->UserIdColumn . ') {
    HTTP::ExitJSON([\'error\'=>\'Invalid Request\'], HTTP_STATUS_UNAUTHORIZED);
}

$returnvals = [];

if(isset($Web->Request->uuid))
{
	/* @var $c ' . $c_name . ' */
	$c = ' . $c_name . '::Get([\'' . $primary[0] . '\'=>$Web->Request->uuid]);
	if(!$c || !$c->VisibleTo($Web->' . $this->UserVar . ')) {
		HTTP::ExitJSON([\'error\'=>\'Invalid Request\'], HTTP_STATUS_UNAUTHORIZED);
    }

	$returnvals[\'serialized\'] = $c->ToArray();
	$returnvals[\'can_delete\'] = $c->CanDelete($Web->' . $this->UserVar . ') ? 1 : 0;
} else {
	$returnvals[\'error\'] = \'' . CapsToSpaces(str_replace('Class', '', $c_name)) . ' - Get: Bad params passed in!\';
}

HTTP::ExitJSON($returnvals);
	';

        $fp = fopen($this->PagesJSONFolder . '/get.json.php', 'w');
        fwrite($fp, $get);
        fclose($fp);
    }

    protected function LookupJSON($c_name)
    {
        $lookup = '<?php
if(!$Web->' . $this->UserVar . ' || !$Web->' . $this->UserVar . '->' . $this->UserIdColumn . ') {
    HTTP::ExitJSON([\'error\'=>\'Invalid Request\'], HTTP_STATUS_UNAUTHORIZED);
}

$returnvals = [];

$search = strtoupper($Web->Request->term);
if(strlen($search) < 1) {
	$returnvals[] = [\'id\' => 0, \'value\' => \'No Results Found\'];
	HTTP::ExitJSON($returnvals);
}

$returnvals = ' . $c_name . '::Suggest($search, $Web->' . $this->UserVar . ');
HTTP::ExitJSON($returnvals);
	';

        $fp = fopen($this->PagesJSONFolder . '/lookup.json.php', 'w');
        fwrite($fp, $lookup);
        fclose($fp);
    }

    protected function DeleteJSON($c_name, $unique, $primary)
    {
        if (!isset($primary[0])) {
            return;
        }

        $unique_php = '';
        $unique_cols = [];
        $u_ret = [];
        if (sizeof($unique)) {
            foreach ($unique as $key => $cols) {
                if (!sizeof($cols)) {
                    continue;
                }

                $u_req = [];
                $u_seq = [];

                foreach ($cols as $u) {
                    $u_req[] = '$Web->Request->' . $u;
                    $u_seq[] = "'$u'=>\$Web->Request->$u";
                    if (!in_array($u, $unique_cols)) {
                        $u_ret[] = "\$returnvals['$u'] = \$Web->Request->$u;";
                        $unique_cols[] = $u;
                    }
                }

                $unique_php .= '
if(' . implode(' && ', $u_req) . ') {
	$t = ' . $c_name . '::Get([' . implode(', ', $u_seq) . ']);
	if($t) {
	    $Web->Request->uuid = $t->' . ($primary[0] ? $primary[0] : $unique[0]) . ';
	}
}
';

            }
        }


        $delete = '<?php
if(!$Web->' . $this->UserVar . ' || !$Web->' . $this->UserVar . '->' . $this->UserIdColumn . ') {
    HTTP::ExitJSON([\'error\'=>\'Invalid Request\'], HTTP_STATUS_UNAUTHORIZED);
}

$returnvals = [];

' . $unique_php . '

if($Web->Request->uuid)
{
	/* @var $c ' . $c_name . ' */
	$c = ' . $c_name . '::Get(["' . $primary[0] . '"=>$Web->Request->uuid]);
	if(is_null($c) || !$c->VisibleTo($Web->' . $this->UserVar . ') || !$c->CanDelete($Web->' . $this->UserVar . ')) {
		HTTP::ExitJSON([\'error\'=>\'Invalid Request\'], HTTP_STATUS_UNAUTHORIZED);
    }

    if($c->IsReferenced()) {
		HTTP::ExitJSON([\'error\'=>\'The record is depended on by other related records\'], HTTP_STATUS_BAD_REQUEST);
    }

	$res = $c->Remove($Web->' . $this->UserVar . ');
	if(!isset($res[\'error\']) || !$res[\'error\'])
	{
		$returnvals[\'success\'] = \'' . CapsToSpaces(str_replace('Class', '', $c_name)) . ' Removed\';
		$returnvals[\'uuid\'] = $Web->Request->uuid;
		' . implode("\r\n\t\t", $u_ret) . '
	} else {
		$returnvals[\'error\'] = $res[\'error\'];
    }
} else {
	$returnvals[\'error\'] = \'' . CapsToSpaces(str_replace('Class', '', $c_name)) . ' - Delete: Bad params passed in!\';
}

HTTP::ExitJSON($returnvals);
';

        $fp = fopen($this->PagesJSONFolder . '/delete.json.php', 'w');
        fwrite($fp, $delete);
        fclose($fp);
    }

    protected function HistoryJSON($c_name, $primary)
    {
        if (!isset($primary[0])) {
            return;
        }
        $history = '<?php
if(!$Web->' . $this->UserVar . ' || !$Web->' . $this->UserVar . '->' . $this->UserIdColumn . ') {
    HTTP::ExitJSON([\'error\'=>\'Invalid Request\'], HTTP_STATUS_UNAUTHORIZED);
}

if(isset($Web->Request->uuid))
{
	/* @var $item ' . $c_name . ' */
	$item = ' . $c_name . '::Get([\'' . $primary[0] . '\'=>$Web->Request->uuid]);
	if(!$item || !$item->VisibleTo($Web->' . $this->UserVar . ')) {
		HTTP::ExitJSON([\'error\'=>\'Invalid Request\'], HTTP_STATUS_UNAUTHORIZED);
    }

} else {
	HTTP::ExitJSON(\'' . CapsToSpaces(str_replace('Class', '', $c_name)) . ' - Get: Bad params passed in!\', HTTP_STATUS_BAD_REQUEST);
}


ob_start();
?>

<table class="table table-bordered" style="width: 800px;">
<thead>
	<tr>
		<th>Rev</th>
		<th>Column</th>
		<th>Value</th>
		<th>Was</th>
		<th>Now</th>
		<th>When</th>
		<th>By</th>
	</tr>
</thead>
<?php $m = sizeof($item->history); foreach($item->history as $i => $cl) {
    /* @var $cl ChangeLogClass */
    foreach($cl->changes_list as $column => $change) {
    if(' . $c_name . '::IgnoreColumn($column)) {
        continue;
    }
?>
<tr>
	<td><?php echo $m - $i; ?></td>
	<td style="white-space: nowrap;"><?php echo ' . $c_name . '::ColumnNameToNiceName($column); ?></td>
	<td><?php echo $item->ValueToNiceValue($column); ?></td>
	<td><?php echo $item->ValueToNiceValue($column, $change->old); ?></td>
	<td><?php echo $item->ValueToNiceValue($column, $change->new); ?></td>
	<td style="white-space: nowrap;"><?php echo Dates::StandardDateTime($cl->created_at); ?></td>
	<td style="white-space: nowrap;"><?php echo $cl->GetUser(); ?></td>
</tr>
<?php } ?>
<?php } ?>
</table>

<?php
$html = ob_get_clean();
$returnvals[\'html\'] = $html;

HTTP::ExitJSON($returnvals);
';

        $fp = fopen($this->PagesJSONFolder . '/history.json.php', 'w');
        fwrite($fp, $history);
        fclose($fp);
    }

    protected function Add($c_name, $table_name, $cols, $primary)
    {
        if (!sizeof($cols)) {
            return;
        }

        if (!sizeof($primary)) {
            return;
        }

        $DatabaseClass = $this->DatabaseClass;

        $res = $DatabaseClass::GetForeignKeys($table_name);
        $refs = [];

        foreach ($res as $fk) {
            if (!is_array($fk->column_name)) {
                /* @var $fk MSSQL_ForeignKey */
                $refs[$fk->column_name] = SQL_Base::TableToClass($this->DatabasePrefix, $fk->foreign_table_name, $this->LowerCaseTables, $this->DatabaseTypePrefix);
            }
        }

        $colors = '';
        $colors_set = '';
        $form = '';

        foreach ($primary as $col) {
            $form .= '<input type="hidden" name="' . $col . '" id="' . $c_name . '_' . $col . '" />' . "\r\n";
        }

        $form .= '
<table class="dialog_form">
';


        foreach ($cols as $col)
            if (!in_array($col->field, $primary)) {
                if (substr($col->field, strlen($col->field) - 6, 6) === '_by_id')
                    continue;
                if (substr($col->field, strlen($col->field) - 3, 3) === '_at')
                    continue;
                if (substr($col->field, strlen($col->field) - 5, 5) === '_file')
                    continue;

                if (isset($refs[$col->field])) {
                    if ($refs[$col->field] === 'ColorClass') {
                        $colors .= '
	$(\'#' . $c_name . '_' . $col->field . '_selected\').html(\'Select One...\');
	$(\'#' . $c_name . '_' . $col->field . '_selected\').css({\'background-color\' : \'#ffffff\'});
				';

                        $color_var = str_replace('_id', '', $col->field);
                        $colors_set .= '
	if(data.serialized.' . $color_var . ') {
		$(\'#' . $c_name . '_' . $col->field . '_selected\').html(\'\');
		$(\'#' . $c_name . '_' . $col->field . '_selected\').css({\'background-color\' : \'#\' + data.serialized.' . $color_var . '});
	}
				';
                    }

                    $form .= '<tr><td class="name">' . FieldToDisplay($col->field) . '</td><td class="field"><?php echo ' . $refs[$col->field] . '::Select(null, [\'name\'=>\'' . $col->field . '\',\'id\'=>\'' . $c_name . '_' . $col->field . '\']); ?></td></tr>' . "\r\n";

                } else
                    switch ($col->type) {
                        case 'text':
                            $form .= '<tr><td class="name">' . FieldToDisplay($col->field) . '</td><td class="field"><textarea class="form-control" name="' . $col->field . '" id="' . $c_name . '_' . $col->field . '"></textarea></td></tr>' . "\r\n";
                            break;

                        case 'bit':
                        case 'tinyint(1)':
                        case 'tinyint':
                            $elem = $c_name . '_' . $col->field;

                            $form .= '<tr><td class="name">' . FieldToDisplay($col->field) . '</td><td class="field">
					<input type="checkbox" id="' . $elem . '" onclick="$(\'#' . $elem . '_hidden\').val(this.checked ? 1 : 0);" />
					<input type="hidden" name="' . $col->field . '" id="' . $elem . '_hidden" value="0" />
					</td></tr>' . "\r\n";
                            break;

                        case 'datetime':
                        case 'timestamp':
                            $form .= '<tr><td class="name">' . FieldToDisplay($col->field) . '</td><td class="field"><input class="time-picker form-control" type="text" name="' . $col->field . '" id="' . $c_name . '_' . $col->field . '" /></td></tr>' . "\r\n";
                            break;

                        case 'date':
                            $form .= '<tr><td class="name">' . FieldToDisplay($col->field) . '</td><td class="field"><input class="date-picker form-control" type="text" name="' . $col->field . '" id="' . $c_name . '_' . $col->field . '" /></td></tr>' . "\r\n";
                            break;

                        case 'varchar(6)':
                            $form .= '<tr><td class="name">' . FieldToDisplay($col->field) . '</td><td class="field"><input class="color form-control" type="text" name="' . $col->field . '" id="' . $c_name . '_' . $col->field . '" /></td></tr>' . "\r\n";
                            break;

                        default:
                            $form .= '<tr><td class="name">' . FieldToDisplay($col->field) . '</td><td class="field"><input class="form-control" type="text" name="' . $col->field . '" id="' . $c_name . '_' . $col->field . '" /></td></tr>' . "\r\n";
                    }
            }

        $form .= '</table>';

        $add = '<script src="/pages/json/' . $c_name . '/controls/add.js"></script>

<div class="modal fade" id="' . $c_name . '_dialog" style="display: none;" tabindex="-1" role="dialog" aria-labelledby="joinModalLabel">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                <h3 class="modal-title" id="' . $c_name . '_dialog_title"></h3>
            </div>
            <div class="modal-body">

<form id="' . $c_name . '_form">
<input type="hidden" id="' . $c_name . '_document_number" />
' . $form . '

</form>
            </div>
            <div class="modal-footer">
                <span style="white-space: nowrap;">

                <button type="button" class="btn btn-primary" onclick="' . $c_name . '.Save();">Save</button>
                <button type="button" class="btn btn-default" data-dismiss="modal">Cancel</button>
                <button type="button" id="btn_' . $c_name . '_delete" class="btn btn-danger" onclick="' . $c_name . '.Delete();">Delete</button>
                </span>
            </div>
        </div>
    </div>
</div>
';

        $fp = fopen($this->PagesJSONControlsFolder . '/add.php', 'w');
        fwrite($fp, $add);
        fclose($fp);

///////////////////////////////////////////////
///////////////////////////////////////////////
///////////////////////////////////////////////
///////////////////////////////////////////////
///////////////////////////////////////////////
///////////////////////////////////////////////

        $js = '


var ' . $c_name . ' = {
    title: "' . $c_name . '",
    save_action: "Saving ' . $c_name . '",
    delete_action: "Deleting ' . $c_name . '",
    _form: "' . $c_name . '_form",
    _class: "' . $c_name . '",
    _dialog: "' . $c_name . '_dialog",
    _active: false,
    _save_callback: function (data) {
        ReloadPage();
    },
    _delete_callback: function (data) {
        ReloadPage();
    },
    _Load: function (data) {
        ShowModal(this._dialog, this.title);

    },
    Load: function (' . $primary[0] . ', save_callback, delete_callback) {
        ClearForm(this._form);

        $("#" + this._class + "_' . $primary[0] . '").val(' . $primary[0] . ');
        if (' . $primary[0] . ') {
            Post("/json/" + ' . $c_name . '._class + "/get.json", {uuid: ' . $primary[0] . '}, function (data) {
                /** @namespace data.can_delete **/
                LoadForm(data, ' . $c_name . '._class);
                if (!data.can_delete) {
                    $("#btn_" + ' . $c_name . '._class + "_delete").hide();
                } else {
                    $("#btn_" + ' . $c_name . '._class + "_delete").show();
                }
            });
        } else {
            $("#btn_" + this._class + "_delete").hide();
        }

        this._active = true;
        this._Load();

        if (save_callback) {
            this._save_callback = save_callback;
        }
        if (delete_callback) {
            this._delete_callback = delete_callback;
        }
    },
    Save: function () {
        if (!this._active) {
            return;
        }
        WaitDialog("Please Wait...", this.save_action);
        this._active = false;
        SaveObject(this._class, {serialized: $("#" + this._form).serialize()}, this._save_callback, this._dialog);
    },
    Delete: function (id) {
        if(id) {
            ConfirmDeleteObject(this._class, this.title, {uuid: id}, "", this._delete_callback, this._dialog);
            return;
        }

        if (!this._active) {
            return;
        }
        this._active = false;

        ConfirmDeleteObject(this._class, this.title, {uuid: $("#" + this._class + "_' . $primary[0] . '").val()}, "", this._delete_callback, this._dialog);
    }
};
';

        $fp = fopen($this->PagesJSONControlsFolder . '/add.js', 'w');
        fwrite($fp, $js);
        fclose($fp);


    }

    protected function History($c_name)
    {
        $add = '<script src="/pages/json/' . $c_name . '/controls/history.js"></script>

<div class="modal fade" id="' . $c_name . '_history_dialog" style="display: none;" tabindex="-1" role="dialog"
     aria-labelledby="joinModalLabel">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span>
                </button>
                <h3 class="modal-title" id="' . $c_name . '_history_dialog_title"></h3>
            </div>
            <div class="modal-body">


                <div id="' . $c_name . '_history_div" style="height: 400px; overflow-y: auto;"></div>

            </div>
        </div>
    </div>
</div>

';
        $fp = fopen($this->PagesJSONControlsFolder . '/history.php', 'w');
        fwrite($fp, $add);
        fclose($fp);


        $add = '
var ' . $c_name . 'History = {
    Load : function(uuid) {
        $(\'#' . $c_name . '_history_div\').html(\'\');
        if (typeof (uuid) === \'undefined\' || !uuid) {
            return;
        }

        Post(\'/json/' . $c_name . '/history.json\', {
            uuid : uuid
        }, function(data) {
            if (data.error) {
                NoticeDialog(\'Error\',data.error);
            } else {
                $(\'#' . $c_name . '_history_dialog_title\').html("' . $c_name . '");
                $(\'#' . $c_name . '_history_div\').html(data.html);
                ShowModal(\'' . $c_name . '_history_dialog\', \'' . CapsToSpaces(str_replace('Class', '', $c_name)) . ' History\');
            }
        });
    }
};
';
        $fp = fopen($this->PagesJSONControlsFolder . '/history.js', 'w');
        fwrite($fp, $add);
        fclose($fp);
    }

    protected function Manage($c_name)
    {
        $page_dir = str_replace('Class', '', $c_name);

        $page = '
<?php /* @var $PageModel ' . $page_dir . ' */ ?>
<div class="panel panel-default">
    <div class="panel-heading">
        <div class="pull-right"><a id="" onclick="' . $c_name . '.Load();"><i class="fa fa-plus"></i></a></div>
        <div class="panel-title">' . CapsToSpaces(str_replace('Class', '', $c_name)) . '</div>
    </div>
    <div class="panel-body">
<?php echo BootstrapPaginationLinks($PageModel->Count); ?>
<table class="table table-striped" style="font-size: 0.9em;">
    <thead>
    <?php echo $PageModel->TableHeader; ?>
    </thead>
    <?php foreach ($PageModel->' . $page_dir . ' as $item) { ?>
        <?php echo $item->ToRow(true); ?>
    <?php } ?>
</table>
<?php echo BootstrapPaginationLinks($PageModel->Count); ?>

    </div>
</div>


<?php require_once \'pages/json/' . $c_name . '/controls/add.php\'; ?>
';

        $fp = fopen($this->PagesManageFolder . '/' . $page_dir . '.php', 'w');
        fwrite($fp, $page);
        fclose($fp);

        $code = '<?php

/**
 * Class ' . $page_dir . '
 *
 * @property ' . $c_name . '[] ' . $page_dir . '
 * @property string Count
 * @property string TableHeader
 */
class ' . $page_dir . ' extends BasePage
{
    public $' . $page_dir . ';
    public $Count;
    public $TableHeader;

    public function Init()
    {
        $this->MasterPage = ' . $this->MasterPage . ';
    }

    public function Get()
    {
        $items = ' . $c_name . '::GetAllPaginated(null, null, PAGE, PER_PAGE);
        $this->TableHeader = ' . $c_name . '::GetHeader(SORT_BY, SORT_DIR, true);
        $this->' . $page_dir . ' = $items[\'items\'];
        $this->Count = $items[\'count\'];

    }
}

$Web->PageMode = QUICKDRY_MODE_INSTANCE;
';
        $fp = fopen($this->PagesManageFolder . '/' . $page_dir . '.code.php', 'w');
        fwrite($fp, $code);
        fclose($fp);

    }

}