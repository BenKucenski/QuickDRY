<?php
namespace QuickDRY\Connectors;

use QuickDRY\Utilities\Log;
use QuickDRY\Utilities\SafeClass;
use QuickDRY\Utilities\Strings;

/**
 * Class SQLCodeGen
 */
class SQLCodeGen extends SafeClass
{
  protected string $DestinationFolder;
  protected string $Database;
  protected string $DatabaseConstant;
  protected string $DatabasePrefix;
  protected string $UserClass;
  protected string $UserVar;
  protected string $UserIdColumn;
  protected string $MasterPage;
  protected array $Tables;
  protected int $LowerCaseTables;
  protected int $UseFKColumnName;
  protected string $DatabaseTypePrefix;
  protected string $DatabaseClass;
  protected int $GenerateJSON;

  protected string $IncludeFolder;
  protected string $CommonFolder;
  protected string $CommonClassFolder;
  protected string $CommonClassDBFolder;
  protected string $CommonClassSPFolder;
  protected string $CommonClassSPDBFolder;
  protected string $PagesBaseJSONFolder;
  protected string $PagesJSONFolder;
  protected string $PagesJSONControlsFolder;

  protected string $PagesBaseManageFolder;
  protected string $PagesManageFolder;
  protected string $PagesPHPUnitFolder;

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

    if (!is_dir($this->PagesBaseJSONFolder)) {
      mkdir($this->PagesBaseJSONFolder);
    }

    if (!is_dir($this->PagesBaseManageFolder)) {
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
  public static function ColumnTypeToProperty($col_type): string
  {
    switch (strtolower($col_type)) {
      case 'varchar':
      case 'char':
      case 'nchar':
      case 'keyword':
      case 'text':
      case 'nvarchar':
      case 'image':
      case 'uniqueidentifier':
      case 'longtext':
      case 'longblob':
        return 'string';

      case 'tinyint unsigned':
      case 'bigint unsigned':
      case 'long':
      case 'bit':
      case 'bigint':
      case 'smallint':
      case 'tinyint':
      case 'numeric':
      case 'int unsigned':
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
    $template = file_get_contents(__DIR__ . '/_templates/sp.txt');
    $vars = [
      'sp_class' => $sp_class,
    ];

    $include_php = $template;
    foreach ($vars as $name => $v) {
      $include_php = str_replace('[[' . $name . ']]', $v, $include_php);
    }

    self::SaveFile($this->CommonClassSPFolder, $sp_class, $include_php);
  }

  public function GenerateDatabaseClass(): ?array
  {
    return null;
  }

  public function GenerateClasses()
  {
    $modules = $this->GenerateDatabaseClass();

    foreach ($this->Tables as $table_name) {
      Log::Insert($table_name, true);

      $DatabaseClass = $this->DatabaseClass;

      if(!method_exists($DatabaseClass, 'GetTableColumns')) {
        exit("$DatabaseClass::GetTableColumns");
      }

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

    $template = file_get_contents(__DIR__ . '/_templates/autoloader.txt');
    $vars = [
      'autoloader_class' => $autoloader_class,
      'mod_map' => implode("\r\n\t\t", $mod_map),
      'DestinationFolder' => $this->DestinationFolder,
    ];

    $include_php = $template;
    foreach ($vars as $name => $v) {
      $include_php = str_replace('[[' . $name . ']]', $v, $include_php);
    }

    fwrite($fp, $include_php);
    fclose($fp);
  }

  /**
   * @param $table_name
   * @param $cols
   * @return string
   */
  function GenerateClass($table_name, $cols): string
  {
    $DatabaseClass = $this->DatabaseClass;
    $class_props = [];

    $c_name = SQL_Base::TableToClass($this->DatabasePrefix, $table_name, $this->LowerCaseTables, $this->DatabaseTypePrefix);
    Log::Insert($c_name, true);

    if(!method_exists($DatabaseClass, 'GetUniqueKeys')) {
      exit("$DatabaseClass::GetUniqueKeys");
    }
    if(!method_exists($DatabaseClass, 'GetPrimaryKey')) {
      exit("$DatabaseClass::GetPrimaryKey");
    }
    if(!method_exists($DatabaseClass, 'GetIndexes')) {
      exit("$DatabaseClass::GetIndexes");
    }



    $props = '';
    $unique = $DatabaseClass::GetUniqueKeys($table_name);
    $primary = $DatabaseClass::GetPrimaryKey($table_name);
    $indexes = $DatabaseClass::GetIndexes($table_name);

    $aliases = [];

    $HasUserLink = false;

    foreach ($cols as $col) {
      /* @var $col MSSQL_TableColumn */ // these are the same for MySQL and MSSQL, only claim it's one to help with code completion
      if ($col->field !== $col->field_alias) {
        $aliases[] = $col;
      }
      $class_props[] = ' * @property ' . SQLCodeGen::ColumnTypeToProperty(preg_replace('/\(.*?\)/si', '', $col->type)) . ' ' . $col->field_alias;
      $props .= "'" . $col->field . "' => ['type' => '" . str_replace('\'', '\\\'', $col->type) . "', 'is_nullable' => " . ($col->null ? 'true' : 'false') . ", 'display' => '" . SQLCodeGen::FieldToDisplay($col->field) . "'],\r\n\t\t";
      if ($col->field === 'user_id') {
        $HasUserLink = true;
      }
    }


    if(!method_exists($DatabaseClass, 'GetForeignKeys')) {
      exit("$DatabaseClass::GetForeignKeys");
    }

    /* @var $refs MSSQL_ForeignKey[]|MySQL_ForeignKey[] */
    $refs = $DatabaseClass::GetForeignKeys($table_name);
    $gets = [];
    $sets = [];

    $foreign_key_props = [];

    $seens_vars = [];

    foreach ($aliases as $alias) {
      /* @var $alias MSSQL_TableColumn */
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
      } else {
        $column_name = $this->UseFKColumnName ? '_' . $fk->column_name : '';
      }
      $var = 'fk_' . preg_replace('/[^a-z0-9]/si', '_', str_replace(' ', '_', $fk->foreign_table_name) . $column_name);

      if (in_array($var, $seens_vars)) {
        Log::Insert(['duplicate FK', $fk], true);
        continue;
      }
      $seens_vars[] = $var;

      $fk_class = SQL_Base::TableToClass($this->DatabasePrefix, $fk->foreign_table_name, $this->LowerCaseTables, $this->DatabaseTypePrefix);

      $class_props[] = ' * @property ' . $fk_class . ' ' . $var;
      $foreign_key_props[] = 'protected ?' . $fk_class . ' $_' . $var . ' = null;';

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

    if(!method_exists($DatabaseClass, 'GetLinkedTables')) {
      exit("$DatabaseClass::GetLinkedTables");
    }

    /* @var $refs MSSQL_ForeignKey[]|MySQL_ForeignKey[] */
    $refs = $DatabaseClass::GetLinkedTables($table_name);
    $fk_counts = [];
    foreach ($refs as $fk) {
      if (is_array($fk->column_name)) {
        $column_name = $this->UseFKColumnName ? '_' . str_ireplace('_ID', '', implode('_', $fk->column_name)) : '';
      } else {
        $column_name = $this->UseFKColumnName ? '_' . str_ireplace('_ID', '', $fk->column_name) : '';
      }
      $var = preg_replace('/[^a-z0-9]/si', '_', str_replace(' ', '_', $fk->foreign_table_name) . $column_name);
      $var = 'fk_' . $var;


      if (in_array($var, $seens_vars)) {
        Log::Insert(['duplicate FK', $fk], true);
        continue;
      }
      $seens_vars[] = $var;

      $fk_class = SQL_Base::TableToClass($this->DatabasePrefix, $fk->foreign_table_name, $this->LowerCaseTables, $this->DatabaseTypePrefix);

      $class_props[] = ' * @property ' . $fk_class . '[] ' . $var;
      $class_props[] = ' * @property ' . $fk_class . '[] _' . $var;
      $class_props[] = ' * @property int ' . $var . 'Count';


      $foreign_key_props[] = 'protected ?array $_' . $var . ' = null;';
      $foreign_key_props[] = 'protected ?int $_' . $var . 'Count = null;';

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

    $unique_code = '';

    foreach ($unique as $columns) {
      $unique_code .= '          [' . (sizeof($columns) ? '\'' . implode('\',\'', $columns) . '\'' : '') . '],' . PHP_EOL;
    }

    $indexes_code = '';
    foreach ($indexes as $key => $columns) {
      $indexes_code .= '        \'' . $key . '\' => [' . (sizeof($columns) ? '\'' . implode('\',\'', $columns) . '\'' : '') . '],' . PHP_EOL;
    }

    $template = file_get_contents(__DIR__ . '/_templates/class_db.txt');
    $vars = [
      'c_name' => $c_name,
      'class_props' => implode("\r\n", $class_props),
      'DatabaseClass' => $DatabaseClass,
      'primary' => (sizeof($primary) ? '[\'' . implode('\',\'', $primary) . '\']' : '[]'),
      'unique' => $unique_code,
      'indexes' => $indexes_code,
      'prop_definitions' => $props,
      'database' => (!$this->DatabaseConstant ? '\'' . $this->Database . '\'' : $this->DatabaseConstant),
      'table_name' => $table_name,
      'DatabasePrefix' => (!$this->DatabaseConstant ? $this->Database : $this->DatabaseConstant),
      'DatabaseTypePrefix' => $this->DatabaseTypePrefix,
      'LowerCaseTable' => ($this->LowerCaseTables ? 1 : 0),
      'foreign_key_props' => implode("\r\n\t", $foreign_key_props),
      'gets' => implode("\r\n        ", $gets),
      'sets' => implode("\r\n        ", $sets),
      'UserClass' => $this->UserClass,
      'IsReferenced' => (sizeof($fk_counts) == 0 ? 'false' : '!($this->' . implode(' + $this->', $fk_counts) . ' == 0)'),
      'HasUserLink' => $HasUserLink ? '
        if(!$this->id) {
            return true;
        }

        if($this->user_id == $user->id) {
            return true;
        }
      ' : '',
    ];

    $include_php = $template;
    foreach ($vars as $name => $v) {
      $include_php = str_replace('[[' . $name . ']]', $v, $include_php);
    }

    $fp = fopen($this->CommonClassDBFolder . '/db_' . $c_name . '.php', 'w');
    fwrite($fp, $include_php);
    fclose($fp);

    $template = file_get_contents(__DIR__ . '/_templates/class.txt');
    $vars = [
      'c_name' => $c_name,
      'class_props' => implode("\r\n", $class_props),
      'HasUserLink' => $HasUserLink ? '
        global $Web;
        if($this->id) {
            if ($this->user_id != $Web->CurrentUser->id) {
                $res[\'error\'] = [\'No Permission\'];
                return $res;
            }
        } else {
            $this->user_id = $Web->CurrentUser->id;
        }
' : '',
    ];

    $include_php = $template;
    foreach ($vars as $name => $v) {
      $include_php = str_replace('[[' . $name . ']]', $v, $include_php);
    }

    self::SaveFile($this->CommonClassFolder, $c_name, $include_php);

    return $c_name;
  }

  public static array $CacheFileLists = [];

  /**
   * @param string $base_folder
   * @param string $file_name
   * @param $data
   * @param bool $force
   */
  public static function SaveFile(string $base_folder, string $file_name, $data, bool $force = false)
  {
    $file = $base_folder . '/' . $file_name . '.php';
    if (!$force) {
      if (!isset(self::$CacheFileLists[$base_folder])) {
        self::$CacheFileLists[$base_folder] = scandir($base_folder);
      }
      $files = self::$CacheFileLists[$base_folder];
      $file_exists = false;
      foreach ($files as $fname) {
        if (strcasecmp($fname, $file_name . '.php') == 0) {
          $file_exists = true;
          if (!(strcmp($fname, $file_name . '.php') == 0)) {
            rename($base_folder . '/' . $file_name . '.php', $file);
          }
          break;
        }
      }


      if (!$file_exists) {
        $fp = fopen($file, 'w');
        fwrite($fp, $data);
        fclose($fp);
      }
    } else {
      $fp = fopen($file, 'w');
      fwrite($fp, $data);
      fclose($fp);
    }
  }

  public function GenerateJSON()
  {
    if (!$this->GenerateJSON) {
      return;
    }


    $DatabaseClass = $this->DatabaseClass;

    foreach ($this->Tables as $table_name) {
      Log::Insert($table_name, true);
      // $c_name = SQL_Base::TableToClass($this->DatabasePrefix, $table_name, $this->LowerCaseTables, $this->DatabaseTypePrefix);

      $this->PagesJSONFolder = $this->PagesBaseJSONFolder . '/' . $this->DatabaseTypePrefix . '_' . strtolower($this->DatabasePrefix);

      if (!is_dir($this->PagesJSONFolder)) {
        mkdir($this->PagesJSONFolder);
      }

      if(!method_exists($DatabaseClass, 'TableToNiceName')) {
        exit("$DatabaseClass::TableToNiceName");
      }

      $table_nice_name = $DatabaseClass::TableToNiceName($table_name, $this->LowerCaseTables);

      $this->PagesJSONFolder .= '/' . $table_nice_name;
      if (!is_dir($this->PagesJSONFolder)) {
        mkdir($this->PagesJSONFolder);
      }

      if (!is_dir($this->PagesJSONFolder . '/base')) {
        mkdir($this->PagesJSONFolder . '/base');
      }

      $this->PagesJSONControlsFolder = $this->PagesJSONFolder . '/controls';
      if (!is_dir($this->PagesJSONControlsFolder)) {
        mkdir($this->PagesJSONControlsFolder);
      }


      $this->PagesManageFolder = $this->PagesBaseManageFolder . '/' . $this->DatabaseTypePrefix . '_' . strtolower($this->DatabasePrefix);

      if (!is_dir($this->PagesManageFolder)) {
        mkdir($this->PagesManageFolder);
      }

      $this->PagesManageFolder .= '/' . $table_nice_name;
      if (!is_dir($this->PagesManageFolder)) {
        mkdir($this->PagesManageFolder);
      }

      if(!method_exists($DatabaseClass, 'GetTableColumns')) {
        exit("$DatabaseClass::GetTableColumns");
      }

      $columns = $DatabaseClass::GetTableColumns($table_name);
      $this->_GenerateJSON($table_name, $table_nice_name, $columns);
    }
  }

  protected function _GenerateJSON($table_name, $table_nice_name, $cols)
  {
    $DatabaseClass = $this->DatabaseClass;

    $c_name = SQL_Base::TableToClass($this->DatabasePrefix, $table_name, $this->LowerCaseTables, $this->DatabaseTypePrefix);

    if(!method_exists($DatabaseClass, 'GetPrimaryKey')) {
      exit("$DatabaseClass::GetPrimaryKey");
    }

    // $unique = $DatabaseClass::GetUniqueKeys($table_name);
    $primary = $DatabaseClass::GetPrimaryKey($table_name);

    $this->Add($c_name, $table_name, $cols, $primary, $table_nice_name);
    $this->History($c_name, $table_nice_name, $primary);
    $this->Manage($c_name, $table_nice_name, $primary);

    $this->CRUDClass($c_name, $table_nice_name, $primary);
  }

  protected function CRUDClass($c_name, $table_nice_name, $primary)
  {
    if (!sizeof($primary)) {
      return;
    }
    $namespace = 'json\\' . $this->DatabaseTypePrefix . '_' . $this->Database;

    $get_params = [];
    $missing_params = [];
    foreach ($primary as $param) {
      $get_params [] = '\'' . $param . '\' => self::$Request->Get(\'' . $param . '\')';
      $missing_params [] = '!self::$Request->Get(\'' . $param . '\')';

    }
    $get_params = implode(', ', $get_params);
    $missing_params = implode(' || ', $missing_params);

    $template = file_get_contents(__DIR__ . '/_templates/crud.txt');
    $vars = [
      'table_nice_name' => $table_nice_name,
      'namespace' => $namespace,
    ];

    $include_php = $template;
    foreach ($vars as $name => $v) {
      $include_php = str_replace('[[' . $name . ']]', $v, $include_php);
    }

    self::SaveFile($this->PagesJSONFolder, $table_nice_name, $include_php);


    $template = file_get_contents(__DIR__ . '/_templates/crud.code.txt');
    $vars = [
      'table_nice_name' => $table_nice_name,
      'namespace' => $namespace,
    ];

    $include_php = $template;
    foreach ($vars as $name => $v) {
      $include_php = str_replace('[[' . $name . ']]', $v, $include_php);
    }

    self::SaveFile($this->PagesJSONFolder, $table_nice_name . '.code', $include_php);

    $template = file_get_contents(__DIR__ . '/_templates/crud_base.txt');
    $vars = [
      'c_name' => $c_name,
      'get_params' => $get_params,
      'table_nice_name' => $table_nice_name,
      'missing_params' => $missing_params,
      'namespace' => $namespace,
    ];

    $include_php = $template;
    foreach ($vars as $name => $v) {
      $include_php = str_replace('[[' . $name . ']]', $v, $include_php);
    }

    self::SaveFile($this->PagesJSONFolder . '/base', $table_nice_name . 'Base', $include_php, true);
  }

  protected function Add(string $c_name, string $table_name, array $cols, array $primary, string $table_nice_name)
  {
    if (!sizeof($cols)) {
      return;
    }

    if (!sizeof($primary)) {
      return;
    }

    $DatabaseClass = $this->DatabaseClass;

    if(!method_exists($DatabaseClass, 'GetForeignKeys')) {
      exit("$DatabaseClass::GetForeignKeys");
    }

    $res = $DatabaseClass::GetForeignKeys($table_name);
    $refs = [];

    foreach ($res as $fk) {
      if (!is_array($fk->column_name)) {
        /* @var $fk MSSQL_ForeignKey */
        $refs[(string)$fk->column_name] = SQL_Base::TableToClass($this->DatabasePrefix, $fk->foreign_table_name, $this->LowerCaseTables, $this->DatabaseTypePrefix);
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
        if ($col->field === 'user_id') {
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

          $form .= '<tr><td class="name">' . SQLCodeGen::FieldToDisplay($col->field) . '</td><td class="field"><?php echo ' . $refs[$col->field] . '::Select(null, new ElementID(\'' . $c_name . '_' . $col->field . '\', \'' . $col->field . '\')); ?></td></tr>' . "\r\n";

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

    $vars = [
      'c_name' => $c_name,
      'primary' => $primary[0],
      'table_nice_name' => $table_nice_name,
      'JSONFolder' => $this->DatabaseTypePrefix . '_' . strtolower($this->DatabasePrefix),
      'form' =>$form,
    ];

    $template = file_get_contents(__DIR__ . '/_templates/add.txt');

    $include_php = $template;
    foreach ($vars as $name => $v) {
      $include_php = str_replace('[[' . $name . ']]', $v, $include_php);
    }

    $fp = fopen($this->PagesJSONControlsFolder . '/add.php', 'w');
    fwrite($fp, $include_php);
    fclose($fp);


    $template = file_get_contents(__DIR__ . '/_templates/add.js.txt');

    $include_php = $template;
    foreach ($vars as $name => $v) {
      $include_php = str_replace('[[' . $name . ']]', $v, $include_php);
    }

    $fp = fopen($this->PagesJSONControlsFolder . '/add.js', 'w');
    fwrite($fp, $include_php);
    fclose($fp);


  }

  /**
   * @param string $c_name
   * @param string $table_nice_name
   * @param array $primary
   */
  protected function History(string $c_name, string $table_nice_name, array $primary)
  {
    if (!sizeof($primary)) {
      return;
    }

    $vars = [
      'c_name' => $c_name,
      'primary' => $primary[0],
      'table_nice_name' => $table_nice_name,
      'JSONFolder' => $this->DatabaseTypePrefix . '_' . strtolower($this->DatabasePrefix),
      'ClassName' =>Strings::CapsToSpaces(str_replace('Class', '', $c_name)),
    ];

    $template = file_get_contents(__DIR__ . '/_templates/history.txt');

    $include_php = $template;
    foreach ($vars as $name => $v) {
      $include_php = str_replace('[[' . $name . ']]', $v, $include_php);
    }

    $fp = fopen($this->PagesJSONControlsFolder . '/history.php', 'w');
    fwrite($fp, $include_php);
    fclose($fp);


    $template = file_get_contents(__DIR__ . '/_templates/history.js.txt');

    $include_php = $template;
    foreach ($vars as $name => $v) {
      $include_php = str_replace('[[' . $name . ']]', $v, $include_php);
    }

    $fp = fopen($this->PagesJSONControlsFolder . '/history.js', 'w');
    fwrite($fp, $include_php);
    fclose($fp);
  }

  /**
   * @param $c_name
   * @param $table_nice_name
   * @param $primary
   */
  protected function Manage($c_name, $table_nice_name, $primary)
  {
    if (!sizeof($primary)) {
      return;
    }

    $namespace = 'manage\\' . $this->DatabasePrefix . '_' . $this->Database;

    $template = file_get_contents(__DIR__ . '/_templates/manage.txt');
    $vars = [
      'c_name' => $c_name,
      'table_nice_name' => $table_nice_name,
      'namespace' => $namespace,
      'DestinationFolder' => str_replace($this->DestinationFolder . '/', '', $this->PagesJSONFolder),
    ];

    $include_php = $template;
    foreach ($vars as $name => $v) {
      $include_php = str_replace('[[' . $name . ']]', $v, $include_php);
    }

    $fp = fopen($this->PagesManageFolder . '/' . $table_nice_name . '.php', 'w');
    fwrite($fp, $include_php);
    fclose($fp);

    $template = file_get_contents(__DIR__ . '/_templates/manage.code.txt');
    $vars = [
      'c_name' => $c_name,
      'namespace' => $namespace,
      'table_nice_name' => $table_nice_name,
    ];

    $include_php = $template;
    foreach ($vars as $name => $v) {
      $include_php = str_replace('[[' . $name . ']]', $v, $include_php);
    }

    $fp = fopen($this->PagesManageFolder . '/' . $table_nice_name . '.code.php', 'w');
    fwrite($fp, $include_php);
    fclose($fp);
  }

  /**
   * @param $field
   *
   * @return string
   */
  public static function FieldToDisplay($field): string
  {
    $t = ucwords(implode(' ', explode('_', $field)));
    $t = str_replace(' ', '', $t);
    if (strcasecmp(substr($t, -2), 'id') == 0)
      $t = substr($t, 0, strlen($t) - 2);
    return Strings::CapsToSpaces($t);
  }
}