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
            case 'nchar':
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
            case 'datetime':
            case 'date':
                return 'DateTime';
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

        $HasUserLink = false;

        foreach ($cols as $col) { /* @var $col MSSQL_TableColumn */ // these are the same for MySQL and MSSQL, only claim it's one to help with code completion
            if($col->field !== $col->field_alias) {
                $aliases[] = $col;
            }
            $class_props[] = ' * @property ' . SQLCodeGen::ColumnTypeToProperty(preg_replace('/\(.*?\)/si', '', $col->type)) . ' ' . $col->field_alias;
            $props .= "'" . $col->field . "' => ['type' => '" . str_replace('\'', '\\\'', $col->type) . "', 'is_nullable' => " . ($col->null ? 'true' : 'false') . ", 'display' => '" . SQLCodeGen::FieldToDisplay($col->field) . "'],\r\n\t\t";
            if($col->field === 'user_id') {
                $HasUserLink = true;
            }
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
' . ($HasUserLink ? '
        if(!$this->id) {
            return true;
        }

        if($this->user_id == $user->id) {
            return true;
        }
' : '') . '
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
' . ($HasUserLink ? '
        if(!$this->id) {
            return true;
        }

        if($this->user_id == $user->id) {
            return true;
        }
' : '') . '
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
     * @param false $force_value
     * @return mixed
     */
    public function ValueToNiceValue($column_name, $value = null, $force_value = false)
    {
        if($value instanceof DateTime) {
            $value = Dates::Timestamp($value, \'\');
        }

        if($value || $force_value) {
            return $value;
        }

        if($this->$column_name instanceof DateTime) {
            return Dates::Timestamp($this->$column_name, \'\');
        }

        return $this->$column_name;
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
' . ($HasUserLink ? '
        global $Web;
        if($this->id) {
            if($this->user_id !== $Web->CurrentUser->id) {
                $res[\'error\'] = [\'No Permission\'];
                return $res;
            }
        } else {
            $this->user_id = $Web->CurrentUser->id;
        }
' : '') . '
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

            $this->PagesJSONFolder = $this->PagesBaseJSONFolder . '/' . $this->DatabaseTypePrefix . '_' . strtolower($this->DatabasePrefix);

            if(!is_dir($this->PagesJSONFolder)) {
                mkdir($this->PagesJSONFolder);
            }

            $table_nice_name = $DatabaseClass::TableToNiceName($table_name, $this->LowerCaseTables);

            $this->PagesJSONFolder .= '/' . $table_nice_name;
            if(!is_dir($this->PagesJSONFolder)) {
                mkdir($this->PagesJSONFolder);
            }

            if(!is_dir($this->PagesJSONFolder . '/base')) {
                mkdir($this->PagesJSONFolder . '/base');
            }

            $this->PagesJSONControlsFolder = $this->PagesJSONFolder . '/controls';
            if(!is_dir($this->PagesJSONControlsFolder)) {
                mkdir($this->PagesJSONControlsFolder);
            }


            $this->PagesManageFolder = $this->PagesBaseManageFolder . '/' . $this->DatabaseTypePrefix . '_' . strtolower($this->DatabasePrefix);

            if(!is_dir($this->PagesManageFolder)) {
                mkdir($this->PagesManageFolder);
            }

            $this->PagesManageFolder .= '/' . $table_nice_name;
            if(!is_dir($this->PagesManageFolder)) {
                mkdir($this->PagesManageFolder);
            }


            $columns = $DatabaseClass::GetTableColumns($table_name);
            $this->_GenerateJSON($table_name, $table_nice_name, $columns);
        }
    }

    protected function _GenerateJSON($table_name, $table_nice_name, $cols)
    {
        $DatabaseClass = $this->DatabaseClass;

        $column_names = [];
        foreach ($cols as $col) {
            $column_names[] = $col->field;
        }

        $c_name = SQL_Base::TableToClass($this->DatabasePrefix, $table_name, $this->LowerCaseTables, $this->DatabaseTypePrefix);

        $unique = $DatabaseClass::GetUniqueKeys($table_name);
        $primary = $DatabaseClass::GetPrimaryKey($table_name);

        $this->Add($c_name, $table_name, $cols, $primary, $table_nice_name);
        $this->History($c_name, $table_nice_name, $primary);
        $this->Manage($c_name, $table_nice_name);

        $this->CRUDClass($c_name, $table_name, $table_nice_name, $primary);

        /**
        $this->SaveJSON($c_name, $primary);
        $this->GetJSON($c_name, $primary);
        $this->LookupJSON($c_name);
        $this->DeleteJSON($c_name, $unique, $primary);
        $this->HistoryJSON($c_name, $primary);
        **/


    }

    protected function CRUDClass($c_name, $table_name, $table_nice_name, $primary)
    {
        if(!sizeof($primary)) {
            return;
        }

        $get_params = [];
        $missing_params = [];
        foreach($primary as $param) {
            $get_params []= '\'' . $param . '\' => self::$Request->' . $param;
            $missing_params []= '!self::$Request->' . $param;

        }
        $get_params = implode(', ', $get_params);
        $missing_params = implode(' || ', $missing_params);

        $code = '<?php
if(!' . $table_nice_name . '::$Item) {
    HTTP::ExitJSON([\'error\'=>\'Invalid Request\'], HTTP_STATUS_BAD_REQUEST);
}
        ';
        $file = $this->PagesJSONFolder . '/' . $table_nice_name . '.php';
        if (!file_exists($file)) {
            $fp = fopen($file, 'w');
            fwrite($fp, $code);
            fclose($fp);
        }

        $code = '<?php
require_once \'base/' . $table_nice_name . 'Base.php\';

class ' . $table_nice_name . ' extends ' . $table_nice_name . 'Base
{

}
        ';
        $file = $this->PagesJSONFolder . '/' . $table_nice_name . '.code.php';
        if (!file_exists($file)) {
            $fp = fopen($file, 'w');
            fwrite($fp, $code);
            fclose($fp);
        }

        $code = '<?php

/**
 * Class ' . $table_nice_name . 'Base
 */
class ' . $table_nice_name . 'Base extends BasePage
{
    public static $History;

    /* @var $Item ' . $c_name . ' */
    public static $Item;

    public static function DoGet()
    {
        self::$Request->FromSerialized(self::$Request->serialized);

        if (!self::$CurrentUser || !self::$CurrentUser->id) {
            HTTP::ExitJSON([\'error\' => \'Invalid Request\'], HTTP_STATUS_UNAUTHORIZED);
        }

        if (' . $missing_params . ') {
            HTTP::ExitJSON([\'error\' => \'Missing ID\'], HTTP_STATUS_BAD_REQUEST);
        }

        self::$Item = ' . $c_name . '::Get([' . $get_params . ']);

        if (!self::$Item) {
            HTTP::ExitJSON([\'error\' => \'Invalid ID\',\'parameters\' => [' . $get_params . ']], HTTP_STATUS_NOT_FOUND);
        }

        if (!self::$Item->VisibleTo(self::$CurrentUser)) {
            HTTP::ExitJSON([\'error\' => \'No Permission\'], HTTP_STATUS_BAD_REQUEST);
        }

        $res = self::$Item->ToJSONArray();
        $res[\'can_delete\'] = self::$Item->CanDelete(self::$CurrentUser);
        HTTP::ExitJSON([\'data\' => $res], HTTP_STATUS_OK);
    }

    public static function DoPost()
    {
        self::$Request->FromSerialized(self::$Request->serialized);

        if (!self::$CurrentUser || !self::$CurrentUser->id) {
            HTTP::ExitJSON([\'error\' => \'Invalid Request\'], HTTP_STATUS_UNAUTHORIZED);
        }

        if (' . $missing_params . ') {
            HTTP::ExitJSON([\'error\' => \'Missing ID\'], HTTP_STATUS_BAD_REQUEST);
        }

        self::$Item = ' . $c_name . '::Get([' . $get_params . ']);
        if (!self::$Item) {
            HTTP::ExitJSON([\'error\' => \'Invalid ID\'], HTTP_STATUS_NOT_FOUND);
        }

        if (!self::$Item->VisibleTo(self::$CurrentUser)) {
            HTTP::ExitJSON([\'error\' => \'No Permission\'], HTTP_STATUS_BAD_REQUEST);
        }

        $req = self::$Request->ToArray();
        $res = self::$Item->FromRequest($req);
        if ($res[\'error\']) {
            HTTP::ExitJSON([\'error\' => $res[\'error\']], HTTP_STATUS_BAD_REQUEST);
        }

        HTTP::ExitJSON([\'data\' => self::$Item->ToArray()], HTTP_STATUS_OK);
    }

    public static function DoPut()
    {
        self::$Request->FromSerialized(self::$Request->serialized);

        if (!self::$CurrentUser || !self::$CurrentUser->id) {
            HTTP::ExitJSON([\'error\' => \'Invalid Request\'], HTTP_STATUS_UNAUTHORIZED);
        }

        self::$Item = new ' . $c_name . '();
        $req = self::$Request->ToArray();
        $res = self::$Item->FromRequest($req, false);

        if ($res[\'error\']) {
            HTTP::ExitJSON([\'error\' => $res[\'error\']], HTTP_STATUS_BAD_REQUEST);
        }

        if (!self::$Item->VisibleTo(self::$CurrentUser)) {
            HTTP::ExitJSON([\'error\' => \'No Permission\'], HTTP_STATUS_BAD_REQUEST);
        }

        $res = self::$Item->Save();
        if ($res[\'error\']) {
            HTTP::ExitJSON([\'error\' => $res[\'error\']], HTTP_STATUS_BAD_REQUEST);
        }

        HTTP::ExitJSON([\'data\' => self::$Item->ToArray()], HTTP_STATUS_OK);
    }

    public static function DoDelete($success_message = \'Item Removed\')
    {
        self::$Request->FromSerialized(self::$Request->serialized);

        if (!self::$CurrentUser || !self::$CurrentUser->id) {
            HTTP::ExitJSON([\'error\' => \'Invalid Request\'], HTTP_STATUS_UNAUTHORIZED);
        }

        if (' . $missing_params . ') {
            HTTP::ExitJSON([\'error\' => \'Missing ID\'], HTTP_STATUS_BAD_REQUEST);
        }

        self::$Item = ' . $c_name . '::Get([' . $get_params . ']);
        if (!self::$Item) {
            HTTP::ExitJSON([\'error\' => \'Invalid ID\'], HTTP_STATUS_NOT_FOUND);
        }

        $res = self::$Item->Remove(self::$CurrentUser);
        if ($res[\'error\']) {
            HTTP::ExitJSON([\'error\' => $res[\'error\']], HTTP_STATUS_BAD_REQUEST);
        }
        HTTP::ExitJSON([\'success\' => $success_message], HTTP_STATUS_OK);
    }

    public static function DoFind()
    {
        self::$Request->FromSerialized(self::$Request->serialized);

        if (!self::$CurrentUser || !self::$CurrentUser->id) {
            HTTP::ExitJSON([\'error\' => \'Invalid Request\'], HTTP_STATUS_UNAUTHORIZED);
        }

        HTTP::ExitJSON([\'error\' => \'Find Not Implemented\'], HTTP_STATUS_BAD_REQUEST);
    }

    public static function DoHistory()
    {
        self::$Request->FromSerialized(self::$Request->serialized);

        if (!self::$CurrentUser || !self::$CurrentUser->id) {
            HTTP::ExitJSON([\'error\' => \'Invalid Request\'], HTTP_STATUS_UNAUTHORIZED);
        }

        if (' . $missing_params . ') {
            HTTP::ExitJSON([\'error\' => \'Missing ID\'], HTTP_STATUS_BAD_REQUEST);
        }

        /* @var self::$Item ' . $c_name . ' */
        self::$Item = ' . $c_name . '::Get([' . $get_params . ']);
        if (!self::$Item || !self::$Item->VisibleTo(self::$CurrentUser)) {
            HTTP::ExitJSON([\'error\' => \'Invalid Request\'], HTTP_STATUS_UNAUTHORIZED);
        }

        self::$History = self::$Item->history;

        if (!self::$History || !sizeof(self::$History)) {
            HTTP::ExitJSON([\'error\' => \'No History Available\'], HTTP_STATUS_NOT_FOUND);
        }

        $report = [];

        $m = sizeof(self::$History);
        foreach (self::$History as $i => $cl) {
            /* @var $cl ChangeLogClass */
            foreach ($cl->changes_list as $column => $change) {
                if (' . $c_name . '::IgnoreColumn($column)) {
                    continue;
                }
                $r = [
                    \'Rev\' => $m - $i,
                    \'Column\' => $column,
                    \'Value\' => self::$Item->ValueToNiceValue($column),
                    \'Was\' => self::$Item->ValueToNiceValue($column, $change->old, true),
                    \'Now\' => self::$Item->ValueToNiceValue($column, $change->new, true),
                    \'When\' => Dates::StandardDateTime($cl->created_at),
                    \'By\' => $cl->GetUser(),

                ];
                $report [] = $r;
            }
        }
        HTTP::ExitJSON([\'history\' => $report], HTTP_STATUS_OK);
    }
}
        ';
        $file = $this->PagesJSONFolder . '/base/' . $table_nice_name . 'Base.php';
        $fp = fopen($file, 'w');
        fwrite($fp, $code);
        fclose($fp);
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
			$returnvals[\'success\'] = \'' . Strings::CapsToSpaces(str_replace('Class', '', $c_name)) . ' Saved!\';
			$returnvals[$primary] = $c->$primary;
			$returnvals[\'serialized\'] = $c->ToArray();
		} else {
			$returnvals[\'error\'] = $res[\'error\'];
        }
	} else {
		$returnvals[\'error\'] = \'' . Strings::CapsToSpaces(str_replace('Class', '', $c_name)) . ' - Save: Bad params passed in!\';
    }
} else {
	$returnvals[\'error\'] = \'' . Strings::CapsToSpaces(str_replace('Class', '', $c_name)) . ' - Save: Bad Request sent!\';
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
	$returnvals[\'error\'] = \'' . Strings::CapsToSpaces(str_replace('Class', '', $c_name)) . ' - Get: Bad params passed in!\';
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

    /**
     * @param $c_name
     * @param $unique
     * @param $primary
     */
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
		$returnvals[\'success\'] = \'' . Strings::CapsToSpaces(str_replace('Class', '', $c_name)) . ' Removed\';
		$returnvals[\'uuid\'] = $Web->Request->uuid;
		' . implode("\r\n\t\t", $u_ret) . '
	} else {
		$returnvals[\'error\'] = $res[\'error\'];
    }
} else {
	$returnvals[\'error\'] = \'' . Strings::CapsToSpaces(str_replace('Class', '', $c_name)) . ' - Delete: Bad params passed in!\';
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
	HTTP::ExitJSON(\'' . Strings::CapsToSpaces(str_replace('Class', '', $c_name)) . ' - Get: Bad params passed in!\', HTTP_STATUS_BAD_REQUEST);
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

    protected function Add($c_name, $table_name, $cols, $primary, $table_nice_name)
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
                if($col->field === 'user_id') {
                    continue;
                }
                if (substr($col->field, strlen($col->field) - 6, 6) === '_by_id') {
                    continue;
                }
                if (substr($col->field, strlen($col->field) - 3, 3) === '_at') {
                    continue;
                }
                if (substr($col->field, strlen($col->field) - 5, 5) === '_file') {
                    continue;
                }

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

                    $form .= '<tr><td class="name">' . SQLCodeGen::FieldToDisplay($col->field) . '</td><td class="field"><?php echo ' . $refs[$col->field] . '::Select(null, [\'name\'=>\'' . $col->field . '\',\'id\'=>\'' . $c_name . '_' . $col->field . '\']); ?></td></tr>' . "\r\n";

                } else
                    switch ($col->type) {
                        case 'text':
                            $form .= '<tr><td class="name">' . SQLCodeGen::FieldToDisplay($col->field) . '</td><td class="field"><textarea class="form-control" name="' . $col->field . '" id="' . $c_name . '_' . $col->field . '"></textarea></td></tr>' . "\r\n";
                            break;

                        case 'bit':
                        case 'tinyint(1)':
                        case 'tinyint':
                            $elem = $c_name . '_' . $col->field;

                            $form .= '<tr><td class="name">' . SQLCodeGen::FieldToDisplay($col->field) . '</td><td class="field">
					<input type="checkbox" id="' . $elem . '" onclick="$(\'#' . $elem . '_hidden\').val(this.checked ? 1 : 0);" />
					<input type="hidden" name="' . $col->field . '" id="' . $elem . '_hidden" value="0" />
					</td></tr>' . "\r\n";
                            break;

                        case 'datetime':
                        case 'timestamp':
                            $form .= '<tr><td class="name">' . SQLCodeGen::FieldToDisplay($col->field) . '</td><td class="field"><input class="time-picker form-control" type="text" name="' . $col->field . '" id="' . $c_name . '_' . $col->field . '" /></td></tr>' . "\r\n";
                            break;

                        case 'date':
                            $form .= '<tr><td class="name">' . SQLCodeGen::FieldToDisplay($col->field) . '</td><td class="field"><input class="form-control" type="date" name="' . $col->field . '" id="' . $c_name . '_' . $col->field . '" /></td></tr>' . "\r\n";
                            break;

                        case 'varchar(6)':
                            $form .= '<tr><td class="name">' . SQLCodeGen::FieldToDisplay($col->field) . '</td><td class="field"><input class="color form-control" type="text" name="' . $col->field . '" id="' . $c_name . '_' . $col->field . '" /></td></tr>' . "\r\n";
                            break;

                        default:
                            $form .= '<tr><td class="name">' . SQLCodeGen::FieldToDisplay($col->field) . '</td><td class="field"><input class="form-control" type="text" name="' . $col->field . '" id="' . $c_name . '_' . $col->field . '" /></td></tr>' . "\r\n";
                    }
            }

        $form .= '</table>';

        $add = '<script src="/pages/json/' . $this->DatabaseTypePrefix . '_' . strtolower($this->DatabasePrefix) . '/' . $table_nice_name . '/controls/add.js"></script>

<div class="modal fade" id="' . $c_name . '_dialog" style="display: none;" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="' . $c_name . '_dialog_title"></h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
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
    title: "' . $table_nice_name . '",
    save_action: "Saving ' . $table_nice_name . '",
    delete_action: "Deleting ' . $table_nice_name . '",
    _form: "' . $c_name . '_form",
    _path: "' . $this->DatabaseTypePrefix . '_' . strtolower($this->DatabasePrefix) .  '/' . $table_nice_name . '",
    _class: "' . $c_name . '",
    _dialog: "' . $c_name . '_dialog",
    _active: false,
    _save_callback: function (data) {
        HTTP.ReloadPage();
    },
    _delete_callback: function (data) {
        HTTP.ReloadPage();
    },
    _Load: function (data) {
        QuickDRY.ShowModal(this._dialog, this.title);

    },
    Load: function (' . $primary[0] . ', save_callback, delete_callback) {
        QuickDRY.ClearForm(this._form);

        $("#" + this._class + "_' . $primary[0] . '").val(' . $primary[0] . ');
        if (' . $primary[0] . ') {
            QuickDRY.Read(' . $c_name . '._path, {' . $primary[0] . ': ' . $primary[0] . '}, function (data) {
                /** @namespace data.can_delete **/
                QuickDRY.LoadForm(data, ' . $c_name . '._class);
                if (!data.data.can_delete) {
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
        if($("#" + this._class + "_' . $primary[0] . '").val()) {
            QuickDRY.Update(this._path, {serialized: $("#" + this._form).serialize()}, this._save_callback, this._dialog);
        } else {
            QuickDRY.Create(this._path, {serialized: $("#" + this._form).serialize()}, this._save_callback, this._dialog);
        }
    },
    Delete: function (' . $primary[0] . ') {
        if(' . $primary[0] . ') {
            QuickDRY.ConfirmDelete(this._path, this.title, {' . $primary[0] . ': ' . $primary[0] . '}, "", this._delete_callback, this._dialog);
            return;
        }

        if (!this._active) {
            return;
        }
        this._active = false;

        QuickDRY.ConfirmDelete(this._path, this.title, {' . $primary[0] . ': $("#" + this._class + "_' . $primary[0] . '").val()}, "", this._delete_callback, this._dialog);
    }
};
';

        $fp = fopen($this->PagesJSONControlsFolder . '/add.js', 'w');
        fwrite($fp, $js);
        fclose($fp);


    }

    /**
     * @param $c_name
     * @param $table_nice_name
     * @param $primary
     */
    protected function History($c_name, $table_nice_name, $primary)
    {
        if (!sizeof($primary)) {
            return;
        }

        $add = '<script src="/pages/json/' . $this->DatabaseTypePrefix . '_' . strtolower($this->DatabasePrefix) . '/' . $table_nice_name . '/controls/history.js"></script>

<div class="modal fade" id="' . $c_name . '_history_dialog" style="display: none;" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="' . $c_name . '_history_dialog_title"></h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">

                <table id="' . $c_name . '_history_table" class="table table-striped" style="font-size: 0.9em;">
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
                    <tbody>

                    </tbody>
                </table>

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

        HTTP.Post(\'/json/' . $this->DatabaseTypePrefix . '_' . strtolower($this->DatabasePrefix) . '/' . $table_nice_name . '\', {
            ' . $primary[0] . ' : uuid,
            verb : \'HISTORY\'
        }, function(data) {
            if (data.error) {
                NoticeDialog(\'Error\',data.error);
            } else {
                $(\'#' . $c_name . '_history_dialog_title\').html("' . $c_name . '");
                for(var i in data.history) {
                    var row = data.history[i];
                    var html = \'<tr>\' +
                        \'<td>\' + row.Rev + \'</td>\' +
                        \'<td>\' + row.Column + \'</td>\' +
                        \'<td>\' + row.Value + \'</td>\' +
                        \'<td>\' + row.Was + \'</td>\' +
                        \'<td>\' + row.Now + \'</td>\' +
                        \'<td>\' + row.When + \'</td>\' +
                        \'<td>\' + row.By + \'</td>\' +
                        \'</tr>\';
                    $(\'#' . $c_name . '_history_table > tbody:last-child\').append(html);
                }
                
                QuickDRY.ShowModal(\'' . $c_name . '_history_dialog\', \'' . Strings::CapsToSpaces(str_replace('Class', '', $c_name)) . ' History\');
            }
        });
    }
};
';
        $fp = fopen($this->PagesJSONControlsFolder . '/history.js', 'w');
        fwrite($fp, $add);
        fclose($fp);
    }

    /**
     * @param $c_name
     * @param $table_nice_name
     */
    protected function Manage($c_name, $table_nice_name)
    {
        $page_dir = str_replace('Class', '', $c_name);

        $page = '<div class="panel panel-default">
    <div class="panel-heading">
        <div class="pull-right"><a id="" onclick="' . $c_name . '.Load();"><i class="fa fa-plus"></i></a></div>
        <div class="panel-title">' . $table_nice_name . '</div>
    </div>
    <div class="panel-body">
<?php echo Navigation::BootstrapPaginationLinks(' . $table_nice_name . '::$Count); ?>
<table class="table table-striped" style="font-size: 0.9em;">
    <thead>
    <?php echo ' . $table_nice_name . '::$TableHeader; ?>
    </thead>
    <?php foreach (' . $table_nice_name . '::$Items as $item) { ?>
        <?php echo $item->ToRow(true); ?>
    <?php } ?>
</table>
<?php echo Navigation::BootstrapPaginationLinks(' . $table_nice_name . '::$Count); ?>

    </div>
</div>


<?php require_once \'' . str_replace($this->DestinationFolder . '/', '', $this->PagesJSONFolder) . '/controls/add.php\'; ?>
';

        $fp = fopen($this->PagesManageFolder . '/' . $table_nice_name . '.php', 'w');
        fwrite($fp, $page);
        fclose($fp);

        $code = '<?php

/**
 * Class ' . $table_nice_name . '
 *
 */
class ' . $table_nice_name . ' extends BasePage
{
    /* @var $Items ' . $c_name . '[]  */
    public static $Items;

    /* @var $Count int */
    public static $Count;

    /* @var $TableHeader string */
    public static $TableHeader;

    public static function DoInit()
    {
        self::$MasterPage = ' . $this->MasterPage . ';
    }

    public static function DoGet()
    {
        $items = ' . $c_name . '::GetAllPaginated(null, null, PAGE, PER_PAGE);
        self::$TableHeader = ' . $c_name . '::GetHeader(SORT_BY, SORT_DIR, true);
        self::$Items = $items[\'items\'];
        self::$Count = $items[\'count\'];

    }
}
';
        $fp = fopen($this->PagesManageFolder . '/' . $table_nice_name . '.code.php', 'w');
        fwrite($fp, $code);
        fclose($fp);

    }

    /**
     * @param $field
     *
     * @return string
     */
    public static function FieldToDisplay($field)
    {
        $t = ucwords(implode(' ', explode('_', $field)));
        $t = str_replace(' ', '', $t);
        if (strcasecmp(substr($t, -2), 'id') == 0)
            $t = substr($t, 0, strlen($t) - 2);
        return Strings::CapsToSpaces($t);
    }
}