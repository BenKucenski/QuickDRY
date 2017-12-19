<?php
class MySQL_CodeGen extends SafeClass
{
    private $Database;
    private $DatabaseConstant;
    private $DatabasePrefix;
    private $UserClass;
    private $UserVar;
    private $UserIdColumn;
    private $MasterPage;
    private $Tables;
    private $LowerCaseTables;
    private $UseFKColumnName;
    private $DatabaseTypePrefix = 'my';

    public function Init($database, $database_constant, $user_class, $user_var, $user_id_column, $master_page, $lowercase_tables, $use_fk_column_name)
    {
        $this->Database = $database;
        $this->DatabaseConstant = $database_constant;
        $this->UserClass = $user_class;
        $this->UserVar = $user_var;
        $this->UserIdColumn = $user_id_column;
        $this->MasterPage = $master_page;
        $this->DatabasePrefix = $this->DatabaseConstant ? $this->DatabaseConstant : $this->Database;
        $this->LowerCaseTables = $lowercase_tables;
        $this->UseFKColumnName = $use_fk_column_name;

        MySQL_A::CopyInfoSchema();

        MySQL_A::SetDatabase($this->Database);

        Log::Insert('$this->Tables = MySQL_A::GetTables();', true);
        $this->Tables = MySQL_A::GetTables();

        if (!is_dir('includes'))
            mkdir('includes');

        if (!is_dir('class'))
            mkdir('class');
        if (!is_dir('class/' . $this->DatabaseTypePrefix . '_' . strtolower($this->DatabasePrefix)))
            mkdir('class/' . $this->DatabaseTypePrefix . '_' . strtolower($this->DatabasePrefix));

        if (!is_dir('db'))
            mkdir('db');
        if (!is_dir('db/' . $this->DatabaseTypePrefix . '_' . strtolower($this->DatabasePrefix)))
            mkdir('db/' . $this->DatabaseTypePrefix . '_' . strtolower($this->DatabasePrefix));
        if (!is_dir('db/' . $this->DatabaseTypePrefix . '_' . strtolower($this->DatabasePrefix) . '/db'))
            mkdir('db/' . $this->DatabaseTypePrefix . '_' . strtolower($this->DatabasePrefix) . '/db');

        if (!is_dir('sp'))
            mkdir('sp');
        if (!is_dir('sp/' . $this->DatabaseTypePrefix . '_' . strtolower($this->DatabasePrefix)))
            mkdir('sp/' . $this->DatabaseTypePrefix . '_' . strtolower($this->DatabasePrefix));
        if (!is_dir('sp/' . $this->DatabaseTypePrefix . '_' . strtolower($this->DatabasePrefix) . '/sp'))
            mkdir('sp/' . $this->DatabaseTypePrefix . '_' . strtolower($this->DatabasePrefix) . '/sp');

        if (!is_dir('phpunit'))
            mkdir('phpunit');

        if (!is_dir('json'))
            mkdir('json');

        if (!is_dir('manage'))
            mkdir('manage');

        if (!is_dir('_common'))
            mkdir('_common');
    }

    public function GenerateSPClassFile($sp_class)
    {
        $code = '<?php

/**
 * Class ' . $sp_class . '
 */
class ' . $sp_class . ' extends SafeClass
{
    public function __construct($row = null)
    {
        if($row) {
            CleanHalt($row);
            $this->FromRow($row);
        }
    }
}
';
        $file = 'sp/' . $this->DatabaseTypePrefix . '_' . strtolower($this->DatabasePrefix) . '/sp/' . $sp_class . '.php';
        $fp = fopen($file, 'w');
        fwrite($fp, $code);
        fclose($fp);
    }

    public function GenerateClasses()
    {
        $modules = [];
        $modules[] = 'require_once \'common/sp_' . $this->DatabaseTypePrefix . '_' . strtolower($this->DatabasePrefix) . '.php\';';
        $this->GenerateDatabaseClass();

        foreach ($this->Tables as $table_name) {
            Log::Insert($table_name, true);

            $columns = MySQL_A::GetTableColumns($table_name);
            $mod = $this->GenerateClass($table_name, $columns);
            $modules[] = 'require_once \'common/' . $this->DatabaseTypePrefix . '_' . strtolower($this->DatabasePrefix) . '/db/db_' . $mod . '.php\';';
            $modules[] = 'require_once \'common/' . $this->DatabaseTypePrefix . '_' . strtolower($this->DatabasePrefix) . '/' . $mod . '.php\';';
        }

        $fp = fopen('includes/' . $this->DatabaseTypePrefix . '_' . strtolower($this->DatabasePrefix) . '.php', 'w');
        fwrite($fp, "<?php\r\n\r\n" . implode("\r\n", $modules));
        fclose($fp);
    }

    function GenerateDatabaseClass()
    {
        $class_name = $this->DatabaseTypePrefix . '_' . strtolower($this->DatabasePrefix);
        $stored_procs = MySQL_A::GetStoredProcs();

        if(!$stored_procs) {
            return;
        }
        $sp_require = [];
        $sp_code = [];
        foreach ($stored_procs as $sp) {
            $sp_class = $class_name . '_' . $sp->SPECIFIC_NAME . 'Class';

            $this->GenerateSPClassFile($sp_class);

            $sp_require[] = 'require_once \'' . $this->DatabaseTypePrefix . '_' . strtolower($this->DatabasePrefix) . '/sp/' . $sp_class . '.php\';';

            $sp_params = MySQL_A::GetStoredProcParams($sp->SPECIFIC_NAME);
            $params = [];
            $sql_params = [];
            foreach ($sp_params as $param) {
                $params[] = str_replace('@', '$', $param->Parameter_name);
                $sql_params[] = $param->Parameter_name . ' = {{}}';
            }

            $sp_code[] = '
            
    /**
     * @return ' . $sp_class . '[]
     */
    public static function ' . $sp->SPECIFIC_NAME . '(' . implode(', ', $params) . ') 
    {
        $sql = \'
        EXEC	\' . ' . $this->DatabaseConstant . ' . \'.[dbo].[' . $sp->SPECIFIC_NAME . ']
        ' . implode(",\r\n\t\t", $sql_params) . '
        
        \';
        /* @var $rows ' . $sp_class . '[] */
        $rows =  MySQL_A::Query($sql, [' . implode(', ', $params) . '], null, function ($row) {
            return new ' . $sp_class . '($row);
        });

        if (isset($rows[\'error\'])) {
            Halt($rows);
        }
        return $rows;
    }
            ';
        }
        $code = '<?php
' . implode("\r\n", $sp_require) . '

class sp_' . $class_name . ' extends MySQL_A 
{
' . implode("\r\n", $sp_code) . '
}
        ';

        $fp = fopen('_common/sp_' . $this->DatabaseTypePrefix . '_' . strtolower($this->DatabasePrefix) . '.php', 'w');
        fwrite($fp, $code);
        fclose($fp);
    }

    function GenerateClass($table_name, $cols)
    {
        $class_props = array();

        $c_name = SQL_Base::TableToClass($this->DatabasePrefix, $table_name, $this->LowerCaseTables, $this->DatabaseTypePrefix);
        Log::Insert($c_name, true);

        $props = '';
        $unique = MySQL_A::GetUniqueKeys($table_name);
        $primary = MySQL_A::GetPrimaryKey($table_name);


        foreach ($cols as $col) {
            $class_props[] = ' * @property ' . ColumnTypeToProperty(preg_replace('/\(.*?\)/si', '', $col->type)) . ' ' . $col->field;
            $props .= "'" . $col->field . "'=>array('display'=>'" . FieldToDisplay($col->field) . "', 'type'=>'" . str_replace('\'', '\\\'', $col->type) . "', 'is_nullable'=>" . (strcasecmp($col->null, 'no') == 0 ? 'false' : 'true') . "),\r\n\t\t";
        }


        $refs = MySQL_A::GetForeignKeys($table_name);
        $gets = array();
        $foreign_key_props = array();

        $seens_vars = [];

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

            $class_props[] = ' * @property ' . MySQL_A::TableToClass($this->DatabasePrefix, $fk->foreign_table_name, $this->LowerCaseTables, $this->DatabaseTypePrefix) . ' ' . $var;
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
                    \$this->_$var = " . MySQL_A::TableToClass($this->DatabasePrefix, $fk->foreign_table_name, $this->LowerCaseTables, $this->DatabaseTypePrefix) . "::Get([" . implode(', ', $get_params) . "]);
                }
                return \$this->_$var;
            ";
            } else {
                $gets[] = "
            case '$var':
                if(!isset(\$this->_$var) && \$this->" . $fk->column_name . ") {
                    \$this->_$var = " . MySQL_A::TableToClass($this->DatabasePrefix, $fk->foreign_table_name, $this->LowerCaseTables, $this->DatabaseTypePrefix) . "::Get(['" . $fk->foreign_column_name . "'=>\$this->" . $fk->column_name . "]);
                }
                return \$this->_$var;
            ";
            }
        }

        $refs = MySQL_A::GetLinkedTables($table_name);
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

            $class_props[] = ' * @property ' . MySQL_A::TableToClass($this->DatabasePrefix, $fk->foreign_table_name, $this->LowerCaseTables, $this->DatabaseTypePrefix) . '[] ' . $var;
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

                $gets[] = "
            case '$var':
                if(is_null(\$this->_$var) && " . implode(' && ', $isset) . ") {
                    \$this->_$var = " . MySQL_A::TableToClass($this->DatabasePrefix, $fk->foreign_table_name, $this->LowerCaseTables, $this->DatabaseTypePrefix) . "::GetAll([" . implode(', ', $get_params) . "]);
                }
                return \$this->_$var;

            case '{$var}Count':
                if(is_null(\$this->_{$var}Count) && " . implode(' && ', $isset) . ") {
                    \$this->_{$var}Count = " . MySQL_A::TableToClass($this->DatabasePrefix, $fk->foreign_table_name, $this->LowerCaseTables, $this->DatabaseTypePrefix) . "::GetCount([" . implode(', ', $get_params) . "]);
                }
                return \$this->_{$var}Count;
            ";

            } else {
                $gets[] = "
            case '$var':
                if(is_null(\$this->_$var) && \$this->" . $fk->column_name . ") {
                    \$this->_$var = " . MySQL_A::TableToClass($this->DatabasePrefix, $fk->foreign_table_name, $this->LowerCaseTables, $this->DatabaseTypePrefix) . "::GetAll(['" . $fk->foreign_column_name . "'=>\$this->" . $fk->column_name . "]);
                }
                return \$this->_$var;

            case '{$var}Count':
                if(is_null(\$this->_{$var}Count) && \$this->" . $fk->column_name . ") {
                    \$this->_{$var}Count = " . MySQL_A::TableToClass($this->DatabasePrefix, $fk->foreign_table_name, $this->LowerCaseTables, $this->DatabaseTypePrefix) . "::GetCount(['" . $fk->foreign_column_name . "'=>\$this->" . $fk->column_name . "]);
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

class db_' . $c_name . ' extends MySQL_A
{
    public static $_primary = [\'' . implode('\',\'', $primary) . '\'];
    public static $_unique = [
    ';

        foreach ($unique as $key => $columns) {
            $code .= '        [' . (sizeof($columns) ? '\'' . implode('\',\'', $columns) . '\'' : '') . '],' . PHP_EOL;
        }


        $code .= '
        ];
    protected static $database = ' . (!$this->DatabaseConstant ? '\'' . $this->Database . '\'' : $this->DatabaseConstant) . ';
    protected static $table = \'' . $table_name . '\';
    protected static $DatabasePrefix = \'' . (!$this->DatabaseConstant ? '\'' . $this->Database . '\'' : $this->DatabaseConstant) . '\';
    protected static $DatabaseTypePrefix = \'' . $this->DatabaseTypePrefix . '\';
    protected static $LowerCaseTable = ' . ($this->LowerCaseTables ? 1 : 0) . ';

    protected static $prop_definitions = array(
        ' . $props . '
    );

    ' . implode("\r\n\t", $foreign_key_props) . '

    public function __get($name)
    {
        switch($name)
        {
            ' . implode("\r\n\t\t", $gets) . '
            default:
                return parent::__get($name);
        }
    }

    public function FromRequest(&$req, $save = true, $overwrite = false)
    {
        return parent::FromRequest($req, $save, $overwrite);
    }

    public static function Suggest($search, ' . $this->UserClass . ' &$user)
    {
        exit(\'Suggest not implemented\');
    }

    public function IsReferenced()
    {
        return 0;
    }

    public function VisibleTo(' . $this->UserClass . ' &$user)
    {
        if($user->Is([ROLE_ID_ADMIN])) {
            return true;
        }

        return false;
    }

    public function CanDelete(' . $this->UserClass . ' &$user)
    {
        if($user->Is([ROLE_ID_ADMIN])) {
            return true;
        }

        return false;
    }

    public static function ColumnNameToNiceName($column_name)
    {
        return isset(static::$prop_definitions[$column_name]) ? static::$prop_definitions[$column_name][\'display\'] : \'<i>unknown</i>\';
    }

    public function ValueToNiceValue($column_name, $value = null)
    {
        if($value instanceof DateTime) {
            return Timestamp($value, \'\');
        }

        if($this->$column_name instanceof DateTime) {
            return Timestamp($this->$column_name, \'\');
        }
            
        return $value ? $value : $this->$column_name;
    }

    public static function IgnoreColumn($column_name)
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
    public static function GetAll($where = null, $order_by = null, $limit = null )
    {
		return parent::GetAll($where, $order_by, $limit);
    }
}
';
        $fp = fopen('db/' . $this->DatabaseTypePrefix . '_' . strtolower($this->DatabasePrefix) . '/db/db_' . $c_name . '.php', 'w');
        fwrite($fp, $code);
        fclose($fp);

        $code = '<?php

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

        $fp = fopen('class/' . $this->DatabaseTypePrefix . '_' . strtolower($this->DatabasePrefix) . '/' . $c_name . '.php', 'w');
        fwrite($fp, $code);
        fclose($fp);

        return $c_name;
    }

    public function GenerateJSON()
    {
        foreach ($this->Tables as $table_name) {
            Log::Insert($table_name, true);

            $columns = MySQL_A::GetTableColumns($table_name);
            $this->_GenerateJSON($table_name, $columns);
        }
    }

    private function _GenerateJSON($table_name, $cols)
    {
        $column_names = array();
        foreach ($cols as $col) {
            $column_names[] = $col->field;
        }

        $c_name = SQL_Base::TableToClass($this->DatabasePrefix, $table_name, $this->LowerCaseTables, $this->DatabaseTypePrefix);

        $unique = MySQL_A::GetUniqueKeys($table_name);
        $primary = MySQL_A::GetPrimaryKey($table_name);
        $dlg_name = 'dlg_' . $c_name;

        if (!is_dir('json/_' . $c_name)) {
            mkdir('json/_' . $c_name);
            mkdir('json/_' . $c_name . '/controls');
        }

        $this->SaveJSON($c_name, $column_names, $table_name, $cols, $unique, $primary, $dlg_name);
        $this->GetJSON($c_name, $table_name, $cols, $unique, $primary, $dlg_name);
        $this->LookupJSON($c_name, $table_name, $cols, $unique, $primary, $dlg_name);
        $this->DeleteJSON($c_name, $table_name, $cols, $unique, $primary, $dlg_name);
        $this->HistoryJSON($c_name, $table_name, $cols, $unique, $primary, $dlg_name);
        $this->Add($c_name, $table_name, $cols, $unique, $primary, $dlg_name);
        $this->History($c_name, $table_name, $cols, $unique, $primary, $dlg_name);
        $this->Manage($c_name, $table_name, $cols, $unique, $primary, $dlg_name);
    }

    private function SaveJSON($c_name, $column_names, $table_name, $cols, $unique, $primary, $dlg_name)
    {
        if (!isset($primary[0])) {
            return;
        }

        $save = '<?php
if(!isset($' . $this->UserVar . ') || !$' . $this->UserVar . '->' . $this->UserIdColumn . ') {
    exit_json([\'error\'=>\'Invalid Request\'], HTTP_STATUS_UNAUTHORIZED);
}

$returnvals = [];
		
if($_SERVER[\'REQUEST_METHOD\'] == \'POST\')
{
	if(isset($_POST[\'serialized\']))
	{
		$req = PostFromSerialized($_POST[\'serialized\']);
		$primary = isset(' . $c_name . '::$_primary[0]) ? ' . $c_name . '::$_primary[0] : \'id\';
		if(isset($req[$primary]) && $req[$primary]) {
			$c = ' . $c_name . '::Get([$primary=>$req[$primary]]);
		} else {
			$c = new ' . $c_name . '();
        }
			
		$c->FromRequest($req, false);
		if(!$c->VisibleTo($' . $this->UserVar . ')) {
			exit_json([\'error\'=>\'Invalid Request\'], HTTP_STATUS_UNAUTHORIZED);
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

exit_json($returnvals);
	';
        $fp = fopen('json/_' . $c_name . '/save.json.php', 'w');
        fwrite($fp, $save);
        fclose($fp);
    }

    private function GetJSON($c_name, $table_name, $cols, $unique, $primary, $dlg_name)
    {
        if (!isset($primary[0])) {
            return;
        }

        $get = '<?php
if(!isset($' . $this->UserVar . ') || !$' . $this->UserVar . '->' . $this->UserIdColumn . ') {
    exit_json([\'error\'=>\'Invalid Request\'], HTTP_STATUS_UNAUTHORIZED);
}

$returnvals = [];

if(isset($Request->uuid))
{
	/* @var $c ' . $c_name . ' */
	$c = ' . $c_name . '::Get([\'' . $primary[0] . '\'=>$Request->uuid]);
	if(!$c || !$c->VisibleTo($' . $this->UserVar . ')) {
		exit_json([\'error\'=>\'Invalid Request\'], HTTP_STATUS_UNAUTHORIZED);
    }

	$returnvals[\'serialized\'] = $c->ToArray();
	$returnvals[\'can_delete\'] = $c->CanDelete($' . $this->UserVar . ') ? 1 : 0;
} else {
	$returnvals[\'error\'] = \'' . CapsToSpaces(str_replace('Class', '', $c_name)) . ' - Get: Bad params passed in!\';
}

exit_json($returnvals);
	';

        $fp = fopen('json/_' . $c_name . '/get.json.php', 'w');
        fwrite($fp, $get);
        fclose($fp);
    }

    private function LookupJSON($c_name, $table_name, $cols, $unique, $primary, $dlg_name)
    {
        $lookup = '<?php
if(!isset($' . $this->UserVar . ') || !$' . $this->UserVar . '->' . $this->UserIdColumn . ') {
    exit_json([\'error\'=>\'Invalid Request\'], HTTP_STATUS_UNAUTHORIZED);
}

$returnvals = [];

$search = strtoupper($Request->term);
if(strlen($search) < 1) {
	$returnvals[] = [\'id\' => 0, \'value\' => \'No Results Found\'];
	exit_json($returnvals);
}

$returnvals = ' . $c_name . '::Suggest($search, $CurrentUser);
exit_json($returnvals);
	';

        $fp = fopen('json/_' . $c_name . '/lookup.json.php', 'w');
        fwrite($fp, $lookup);
        fclose($fp);
    }

    private function DeleteJSON($c_name, $table_name, $cols, $unique, $primary, $dlg_name)
    {
        if (!isset($primary[0])) {
            return;
        }

        $unique_php = '';
        $u_req = [];
        $u_seq = [];
        $u_ret = [];
        if (sizeof($unique)) {
            foreach ($unique as $key => $cols) {
                foreach ($cols as $u) {
                    $u_req[] = '$Request->' . $u;
                    $u_seq[] = "'$u'=>\$Request->$u";
                    $u_ret[] = "\$returnvals['$u'] = \$Request->$u;";
                }
            }

            $unique_php = '
if(' . implode(' && ', $u_req) . ')
{
	$t = ' . $c_name . '::Get([' . implode(', ', $u_seq) . ']);
	$Request->uuid = $t->' . ($primary[0] ? $primary[0] : $unique[0]) . ';
}
';
        }


        $delete = '<?php
if(!isset($' . $this->UserVar . ') || !$' . $this->UserVar . '->' . $this->UserIdColumn . ') {
    exit_json([\'error\'=>\'Invalid Request\'], HTTP_STATUS_UNAUTHORIZED);
}

$returnvals = [];

' . $unique_php . '

if($Request->uuid)
{
	/* @var $c ' . $c_name . ' */
	$c = ' . $c_name . '::Get(["' . $primary[0] . '"=>$Request->uuid]);
	if(is_null($c) || !$c->VisibleTo($CurrentUser) || !$c->CanDelete($' . $this->UserVar . ')) {
		exit_json([\'error\'=>\'Invalid Request\'], HTTP_STATUS_UNAUTHORIZED);
    }

	$res = $c->Remove($' . $this->UserVar . ');
	if(!isset($res[\'error\']) || !$res[\'error\'])
	{
		$returnvals[\'success\'] = \'' . CapsToSpaces(str_replace('Class', '', $c_name)) . ' Removed\';
		$returnvals[\'uuid\'] = $Request->uuid;
		' . implode("\r\n\t\t", $u_ret) . '
	} else {
		$returnvals[\'error\'] = $res[\'error\'];
    }
} else {
	$returnvals[\'error\'] = \'' . CapsToSpaces(str_replace('Class', '', $c_name)) . ' - Delete: Bad params passed in!\';
}

exit_json($returnvals);
';

        $fp = fopen('json/_' . $c_name . '/delete.json.php', 'w');
        fwrite($fp, $delete);
        fclose($fp);
    }

    private function HistoryJSON($c_name, $table_name, $cols, $unique, $primary, $dlg_name)
    {
        if (!isset($primary[0])) {
            return;
        }
        $history = '<?php
if(!isset($' . $this->UserVar . ') || !$' . $this->UserVar . '->' . $this->UserIdColumn . ') {
    exit_json([\'error\'=>\'Invalid Request\'], HTTP_STATUS_UNAUTHORIZED);
}

if(isset($Request->uuid))
{
	/* @var $item ' . $c_name . ' */
	$item = ' . $c_name . '::Get([\'' . $primary[0] . '\'=>$Request->uuid]);
	if(!$item || !$item->VisibleTo($' . $this->UserVar . ')) {
		exit_json([\'error\'=>\'Invalid Request\'], HTTP_STATUS_UNAUTHORIZED);
    }

} else {
	exit_json(\'' . CapsToSpaces(str_replace('Class', '', $c_name)) . ' - Get: Bad params passed in!\', HTTP_STATUS_BAD_REQUEST);
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
    if($item->IgnoreColumn($column)) {
        continue;
    }
?>
<tr>
	<td><?php echo $m - $i; ?></td>
	<td style="white-space: nowrap;"><?php echo $item->ColumnNameToNiceName($column); ?></td>
	<td><?php echo $item->ValueToNiceValue($column); ?></td>
	<td><?php echo $item->ValueToNiceValue($column, $change->old); ?></td>
	<td><?php echo $item->ValueToNiceValue($column, $change->new); ?></td>
	<td style="white-space: nowrap;"><?php echo StandardDateTime($cl->created_at); ?></td>
	<td style="white-space: nowrap;"><?php echo $cl->GetUser(); ?></td>
</tr>
<?php } ?>
<?php } ?>
</table>

<?php
$html = ob_get_clean();
$returnvals[\'html\'] = $html;

exit_json($returnvals);
';

        $fp = fopen('json/_' . $c_name . '/history.json.php', 'w');
        fwrite($fp, $history);
        fclose($fp);
    }

    private function Add($c_name, $table_name, $cols, $unique, $primary, $dlg_name)
    {
        if (!sizeof($cols)) {
            return;
        }

        if (!sizeof($primary)) {
            return;
        }

        $res = MySQL_A::GetForeignKeys($table_name);
        $refs = array();

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

        $add = '<script src="/pages/json/_' . $c_name . '/controls/add.js"></script>

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

        $fp = fopen('json/_' . $c_name . '/controls/add.php', 'w');
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
            Post("/json/_" + ' . $c_name . '._class + "/get.json", {uuid: ' . $primary[0] . '}, function (data) {
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

        $fp = fopen('json/_' . $c_name . '/controls/add.js', 'w');
        fwrite($fp, $js);
        fclose($fp);


    }

    private function History($c_name, $table_name, $cols, $unique, $primary, $dlg_name)
    {
        $add = '<script src="/pages/json/_' . $c_name . '/controls/history.js"></script>

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
        $fp = fopen('json/_' . $c_name . '/controls/history.php', 'w');
        fwrite($fp, $add);
        fclose($fp);


        $add = '
var ' . $c_name . 'History = {
    Load : function(uuid) {
        $(\'#' . $c_name . '_history_div\').html(\'\');
        if (typeof (uuid) == \'undefined\' || !uuid) {
            return;
        }
        
        Post(\'/json/_' . $c_name . '/history.json\', {
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
        $fp = fopen('json/_' . $c_name . '/controls/history.js', 'w');
        fwrite($fp, $add);
        fclose($fp);
    }

    private function Manage($c_name, $table_name, $cols, $unique, $primary, $dlg_name)
    {
        $page_dir = str_replace('Class', '', $c_name);

        $page = '<div class="tab_nav">
    <div class="tab_top tab_selected">' . CapsToSpaces(str_replace('Class', '', $c_name)) . '</div>
    <div class="tab tab_top"><a id="" onclick="' . $c_name . '.Load();">New</a></div>
</div>

<?php echo BootstrapPaginationLinks($PageModel->Count); ?>
<table class="table table-striped">
    <thead>
    <?php echo $PageModel->TableHeader; ?>
    </thead>
    <?php foreach ($PageModel->' . $page_dir . ' as $item) { ?>
        <?php echo $item->ToRow(true); ?>
    <?php } ?>
</table>
<?php echo BootstrapPaginationLinks($PageModel->Count); ?>

<?php require_once \'pages/json/_' . $c_name . '/controls/add.php\'; ?>
';

        if (!is_dir('manage/' . $page_dir)) {
            mkdir('manage/' . $page_dir);
        }

        $fp = fopen('manage/' . $page_dir . '/' . $page_dir . '.php', 'w');
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
        $this->MasterPage = \'' . $this->MasterPage . '\';
    }
    
    public function Get() 
    {
        $items = ' . $c_name . '::GetAllPaginated(null, null, PAGE, PER_PAGE);
        $this->TableHeader = ' . $c_name . '::GetHeader(SORT_BY, SORT_DIR, true);
        $this->' . $page_dir . ' = $items[\'items\'];
        $this->Count = $items[\'count\'];

    }
}

$PageModel = new ' . $page_dir . '($Request, $Session, $Cookie);
';
        $fp = fopen('manage/' . $page_dir . '/' . $page_dir . '.code.php', 'w');
        fwrite($fp, $code);
        fclose($fp);

    }
}