<?php

/**
 * Class SQL_Base
 * @property bool HasChanges
 * @property array Changes
 */
class SQL_Base
{
    protected $props = [];
    protected static $table = null;
    protected static $database = null;

    protected $_change_log = [];
    protected $_history = null;
    protected $_from_db = null;

    public $HasChanges;

    public static $UseLog = false;
    public static $Log = [];

    /**
     * @param $database_prefix
     * @param $table
     * @param $lowercase_table
     * @param $database_type_prefix
     * @return string
     */
    public static function TableToClass($database_prefix, $table, $lowercase_table, $database_type_prefix)
    {
        if ($lowercase_table) {
            $table = strtolower($table);
        }

        $database_prefix = strtolower($database_prefix);
        $t = explode('_', $database_prefix . '_' . $table);

        $type = '';
        foreach ($t as $w) {
            $type .= preg_replace('/[^a-z0-9]/si', '', ucfirst($w));
        }
        $type .= 'Class';
        if (is_numeric($type[0]))
            $type = 'i' . $type;
        return $database_type_prefix . '_' . $type;
    }

    /**
     * @param $database_prefix
     * @param $table
     * @param $lowercase_table
     * @param $database_type_prefix
     * @return string
     */
    public static function StoredProcToClass($database_prefix, $table, $lowercase_table, $database_type_prefix)
    {
        // note: we need to leave underscores in
        // underscores are valid PHP and a naming choice for developers
        if ($lowercase_table) {
            $table = strtolower($table);
        }

        $database_prefix = strtolower($database_prefix);
        $t = explode('_', $database_prefix . '_' . $table);

        $type = [];
        foreach ($t as $w) {
            $type [] = preg_replace('/[^a-z0-9]/si', '', ucfirst($w));
        }
        $type = implode('_', $type);
        $type .= 'Class';
        if (is_numeric($type[0])) {
            $type = 'i' . $type;
        }
        return $database_type_prefix . '_' . $type;
    }

    /**
     * @param $database_prefix
     * @param $table
     * @param $lowercase_table
     * @param $database_type_prefix
     * @return string
     */
    public static function TableToNiceName($table, $lowercase_table)
    {
        if ($lowercase_table) {
            $table = strtolower($table);
        }

        $t = explode('_', $table);

        $type = '';
        foreach ($t as $w) {
            $type .= preg_replace('/[^a-z0-9]/si', '', ucfirst($w));
        }

        if (is_numeric($type[0])) {
            $type = 'i' . $type;
        }
        return $type;
    }

    /**
     *
     */
    public function __construct()
    {
        $this->props = self::GetVars();
    }

    /**
     * @return bool
     */
    public function HasChangeLog()
    {
        if (defined('DISABLE_CHANGE_LOG') && DISABLE_CHANGE_LOG) {
            return false;
        }
        if (strcasecmp(static::$table, 'change_log') == 0) { // don't change log the change log
            return false;
        }
        if (strcasecmp(static::$table, 'changelog') == 0) { // don't change log the change log
            return false;
        }

        if (!sizeof($this->_change_log)) { // don't log when nothing changed
            return false;
        }

        return !isset(static::$_use_change_log) || static::$_use_change_log;
    }

    /**
     * @param $var
     */
    public function Clear($var)
    {
        $this->{'_' . $var} = null;
    }

    /**
     * @param $name
     *
     * @return bool
     */
    public function __isset($name)
    {
        return isset(static::$prop_definitions[$name]);
    }

    /**
     * @param $c
     */
    public static function GenProps($c)
    {
        $o = new $c();
        $object = new ReflectionObject($o);
        $method = $object->getMethod('__get');
        $declaringClass = $method->getDeclaringClass();
        $filename = $declaringClass->getFileName();

        $props = [];

        $fp = fopen($filename, 'r');
        $code = fread($fp, filesize($filename));
        fclose($fp);
        $orig_code = $code;
        $pattern = '/@property\s+(.*?)[\r\n]/si';
        $matches = [];
        preg_match_all($pattern, $code, $matches);

        foreach ($matches[1] as $var) {
            $parts = explode(' ', $var);
            $props[$parts[sizeof($parts) - 1]] = trim(str_replace($parts[sizeof($parts) - 1], '', $var));
        }


        $pattern = '/public function __get\(\$name\)(.*?)\n\t}/si';
        $matches = [];
        preg_match($pattern, $code, $matches);
        if (isset($matches[1])) {
            $code = $matches[1];
            $pattern = '/case \'(.*?)\':/si';
            $matches = [];
            preg_match_all($pattern, $code, $matches);
            if (isset($matches[1])) {
                $code = $matches[1];
                foreach ($code as $get) {
                    if (!isset($props[$get]))
                        $props[$get] = 'undefined';
                }
            }
        }
        ksort($props);
        $php = '<?php

/**
 * @author Ben Kucenski
 * QuickDRY Framework ' . date('Y') . '
 *
';
        foreach ($props as $var => $type)
            $php .= ' * @property ' . $type . ' ' . $var . "\r\n";

        $php .= ' */';
        $code = preg_replace('/\<\?php\s+\/\*\*.*?\*\//si', '', $orig_code);
        $code = $php . $code;

        $fp = fopen($filename, 'w');
        fwrite($fp, $code);
        fclose($fp);
    }

    /**
     * @param $name
     *
     */
    public function __get($name)
    {
        switch ($name) {
            case 'Changes':
                return $this->_change_log;

            case 'history':
                if (is_null($this->_history)) {
                    $this->_history = $this->_history();
                }
                return $this->_history;

            default:
                return $this->GetProperty($name);
        }
    }

    /**
     * @param $name
     * @param $value
     * @return mixed
     */
    public function __set($name, $value)
    {
        switch ($name) {
            default:
                $this->SetProperty($name, $value);
        }
        return $value;
    }

    /**
     * @return elastic_ChangeLogDataClass[]|null
     */
    private function _history()
    {
        if (class_exists('ChangeLogHandler')) {
            return ChangeLogHandler::GetHistory(static::$DB_HOST, static::$database, static::$table, $this->GetUUID());
        }
        return null;
    }

    /**
     * @param $key
     *
     * @return bool
     */
    public static function check_props($key)
    {
        return isset(static::$prop_definitions[$key]);
    }

    /**
     * @return array
     */
    public static function GetColumns()
    {
        $cols = [];
        foreach (static::$prop_definitions as $name => $def) {
            $cols[] = $name;
        }
        return $cols;
    }

    /**
     * @param $where
     *
     * @return mixed
     */
    public static function Get($where)
    {
        return static::_Get($where);
    }

    /**
     * @param null $where
     * @param null $order_by
     * @param null $limit
     *
     * @return null
     */
    public static function GetAll($where = null, $order_by = null, $limit = null)
    {
        if (!is_null($order_by) && !is_array($order_by)) {
            Halt('QuickDRY Error: GetAll $order_by must be an assoc array ["col"=>"asc,desc",...]', true);
        }

        if (!is_null($where) && !is_array($where)) {
            Halt('QuickDRY Error: GetAll $where must be an assoc array ["col"=>"val",...]', true);
        }

        if (!is_null($order_by)) {
            foreach ($order_by as $col => $dir) {
                if (!self::check_props(trim($col))) {
                    Halt('QuickDRY Error: ' . $col . ' is not a valid order by column for ' . get_called_class());
                    return null;
                }
            }
        }

        if (is_array($where) && sizeof($where) == 0) {
            $where = null;
        }

        if (!is_null($where)) {
            foreach ($where as $col => $dir) {
                $col = str_replace('+', '', $col);
                if (!self::check_props(trim($col))) {
                    Halt('QuickDRY Error: ' . $col . ' is not a valid where column for ' . get_called_class());
                    return null;
                }
            }
        }

        return static::_GetAll($where, $order_by, $limit);
    }

    /**
     * @param array $where
     *
     * @return mixed
     */
    public static function GetCount($where = null)
    {
        return static::_GetCount($where);
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
     * @return null
     */
    public static function GetAllPaginated($where = null, $order_by = null, $page = 0, $per_page = 0, $left_join = null, $limit = null)
    {
        return static::_GetAllPaginated($where, $order_by, $page, $per_page, $left_join, $limit);
    }

    /**
     * @return array
     */
    public static function GetVars()
    {
        $vars = [];
        foreach (static::$prop_definitions as $name => $def)
            $vars[$name] = null;
        return $vars;
    }

    /**
     * @param string|null $name
     * @return mixed|null
     */
    protected function GetProperty($name = null)
    {
        if (array_key_exists($name, $this->props)) {
            return $this->props[$name];
        }
        Halt($name . ' is not a property of ' . get_class($this) . "\r\n");
        return null;
    }

    public function ClearProps()
    {
        foreach ($this->props as $n => $v) {
            $this->props[$n] = null;
        }
    }

    /**
     * @param $user
     *
     * @return array
     */
    public function ToArray()
    {
        return ToArray($this->props, true, static::$prop_definitions);
    }

    /**
     * @param $user
     *
     * @return array
     */
    public function ToJSONArray()
    {
        return ToArray($this->props, false, static::$prop_definitions);
    }

    /**
     * @param $name
     * @param string $value
     *
     */
    protected function SetProperty($name, $value)
    {
        if (!array_key_exists($name, $this->props)) {
            Halt('QuickDRY Error: ' . $name . ' is not a property of ' . get_class($this) . "\r\n");
        }

        if (is_array($value)) {
            Halt(['QuickDRY Error: Value assigned to property cannot be an array.', $value]);
        }

        if (is_object($value)) {
            if ($value instanceof DateTime) {
                $value = Dates::Timestamp($value);
            } else {
                Halt(['QuickDRY Error: Value assigned to property cannot be an object.', $value]);
            }
        }

        if (strcasecmp($value, 'null') == 0) {
            $value = null;
        }

        $old_val = static::StrongType($name, $this->props[$name]);
        $new_val = static::StrongType($name, $value);

        $changed = false;
        $change_reason = '';
        if (is_null($old_val) && !is_null($new_val)) {
            $changed = true;
            $change_reason = 'old = null, new not null';
        } else {
            if (!is_null($old_val) && is_null($new_val)) {
                $changed = true;
                $change_reason = 'old not null, new null';
            } else {
                if(strlen($old_val) != strlen($new_val)) {
                    $changed = true;
                    $change_reason = '"' . $new_val . '" "' . $old_val . '" ' . strlen($new_val) . ' ' . strlen($old_val) . ': strcmp = ' . strcmp($new_val, $old_val);
                } else {
                    if (is_numeric($old_val) && is_numeric($new_val)) {

                        if (abs($new_val - $old_val) > 0.000000001) {
                            /**
                             * [new] => 5270.6709775679 -- PHP thinks these two numbers are different, so we need to compare to a very small number, not equal
                             * [old] => 5270.6709775679
                             * // from PHP's manual "never trust floating number results to the last digit, and do not compare floating point numbers directly for equality" - https://www.php.net/manual/en/language.types.float.php
                             */
                            $changed = true;
                            $change_reason = 'diff = ' . abs($new_val - $old_val);
                        }
                    } else {
                        if (strcmp($new_val, $old_val) != 0) {
                            $changed = true;
                            $change_reason = '"' . $new_val . '" "' . $old_val . '" ' . strlen($new_val) . ' ' . strlen($old_val) . ': strcmp = ' . strcmp($new_val, $old_val);
                        }
                    }
                }
            }
        }
        if ($changed) {
            if (is_null($new_val)) {
                $new_val = 'null';
            }
            if (is_null($old_val)) {
                $old_val = 'null';
            }
            $this->_change_log[$name] = ['new' => $new_val, 'old' => $old_val, 'reason' => $change_reason];
            $this->HasChanges = true;
        }
        $this->props[$name] = $value;
    }

    /**
     * @param string $sort_by
     * @param string $dir
     * @param bool $modify
     * @param array $add
     * @param array $ignore
     * @param string $add_params
     * @param bool $sortable
     * @param array $column_order
     *
     * @return string
     */
    public static function GetHeader($sort_by = '', $dir = '', $modify = false, $add = [], $ignore = [], $add_params = '', $sortable = true, $column_order = [])
    {
        return static::_GetHeader(static::$prop_definitions, $sort_by, $dir, $modify, $add, $ignore, $add_params, $sortable, $column_order);
    }

    /**
     * @param string $sort_by
     * @param string $dir
     * @param bool $modify
     * @param array $add
     * @param array $ignore
     * @param string $add_params
     * @param bool $sortable
     * @param array $column_order
     *
     * @return string
     */
    public static function GetBareHeader($sort_by = '', $dir = '', $modify = false, $add = [], $ignore = [], $add_params = '', $sortable = true, $column_order = [])
    {
        return static::_GetBareHeader(static::$prop_definitions, $modify, $add, $ignore, $sortable, $column_order);
    }

    /**
     * @param        $props
     * @param        $sort_by
     * @param        $dir
     * @param bool $modify
     * @param array $add
     * @param array $ignore
     * @param string $add_params
     * @param bool $sortable
     * @param array $column_order
     *
     * @return string
     */
    protected static function _GetHeader(&$props, $sort_by, $dir, $modify = false, $add = [], $ignore = [], $add_params = '', $sortable = true, $column_order = [])
    {
        $not_dir = $dir == 'asc' ? 'desc' : 'asc';
        $arrow = $dir == 'asc' ? '&uarr;' : '&darr;';

        $columns = [];
        if(!$add) {
            $add = [];
        }
        if(!$ignore) {
            $ignore = [];
        }

        foreach ($props as $name => $info)
            if (!in_array($name, $ignore))
                if ($sortable)
                    $columns[$name] = '<th><a href="' . CURRENT_PAGE . '?sort_by=' . $name . '&dir=' . (strcasecmp($sort_by, $name) == 0 ? $not_dir : 'asc') . '&per_page=' . PER_PAGE . '&' . $add_params . '">' . static::ColumnNameToNiceName($name) . '</a>' . (strcasecmp($sort_by, $name) == 0 ? ' ' . $arrow : '') . '</th>';
                else
                    $columns[$name] = '<th>' . static::ColumnNameToNiceName($name) . '</th>';

        if (sizeof($add) > 0)
            foreach ($add as $header => $value) {
                if (is_array($value) && $sortable)
                    $columns[$value['value']] = '<th><a href="' . CURRENT_PAGE . '?sort_by=' . $value['sort_by'] . '&dir=' . ($sort_by == $value['sort_by'] ? $not_dir : 'asc') . '&per_page=' . PER_PAGE . '&' . $add_params . '">' . $header . '</a>' . ($sort_by == $value['sort_by'] ? ' ' . $arrow : '') . '</th>';
                else {
                    if (is_array($value))
                        $columns[$value['value']] = '<th>' . $header . '</th>';
                    else
                        $columns[$value] = '<th>' . $header . '</th>';
                }
            }

        $res = '<thead><tr>';
        if (sizeof($column_order) > 0) {
            foreach ($column_order as $order)
                $res .= $columns[$order];
        } else
            foreach ($columns as $column)
                $res .= $column;

        if ($modify)
            $res .= '<th>Action</th>';

        return $res . '</tr></thead>';
    }

    /**
     * @param        $props
     * @param        $sort_by
     * @param        $dir
     * @param bool $modify
     * @param array $add
     * @param array $ignore
     * @param string $add_params
     * @param bool $sortable
     * @param array $column_order
     *
     * @return string
     */
    protected static function _GetBareHeader(&$props, $modify = false, $add = [], $ignore = [], $sortable = true, $column_order = [])
    {
        $columns = [];

        foreach ($props as $name => $info)
            if (!in_array($name, $ignore))
                if ($sortable)
                    $columns[$name] = '<th>' . $info['display'] . '</th>' . "\r\n";
                else
                    $columns[$name] = '<th>' . $info['display'] . '</th>' . "\r\n";

        if (sizeof($add) > 0)
            foreach ($add as $header => $value) {
                if (is_array($value) && $sortable)
                    $columns[$value['value']] = '<th>' . $header . '</th>' . "\r\n";
                else {
                    if (is_array($value))
                        $columns[$value['value']] = '<th>' . $header . '</th>' . "\r\n";
                    else
                        $columns[$value] = '<th>' . $header . '</th>' . "\r\n";
                }
            }

        $res = '<thead><tr>';
        if (sizeof($column_order) > 0) {
            foreach ($column_order as $order)
                $res .= $columns[$order];
        } else
            foreach ($columns as $column)
                $res .= $column;

        if ($modify)
            $res .= '<th>Action</th>' . "\r\n";

        return $res . '</tr></thead>';
    }

    /**
     * @param bool $modify
     * @param array $swap
     * @param array $add
     * @param array $ignore
     * @param string $custom_link
     * @param string $row_style
     * @param array $column_order
     *
     * @return string
     */
    public function ToRowLegacy($modify = false, $swap = [], $add = [], $ignore = [], $custom_link = '', $row_style = '', $column_order = [])
    {
        $res = '<tr style="' . $row_style . '">';
        $columns = [];

        foreach ($this->props as $name => $value)
            if (!in_array($name, $ignore)) {
                if (array_key_exists($name, $swap))
                    $value = $this->{$swap[$name]};
                else
                    $value = $this->$name;


                if (is_array($value))
                    $value = implode(',', $value);
                $class = 'data_text';
                if (is_numeric(str_replace('-', '', $value)))
                    $class = 'data_num';

                $columns[$name] = '<td class="' . $class . '">' . $value . '</td>';
            }

        if (sizeof($add) > 0)
            foreach ($add as $header => $value) {
                if (is_array($value)) {
                    $name = $value['value'];
                    $value = $this->{$value['value']};
                } else {
                    $name = $value;
                    $value = $this->$value;
                }
                if (is_array($value))
                    $value = implode(',', $value);
                $class = 'data_text';
                if (is_numeric(str_replace('-', '', $value)))
                    $class = 'data_num';

                $columns[$name] = '<td class="' . $class . '">' . $value . '</td>';
            }
        if (sizeof($column_order) > 0) {
            foreach ($column_order as $name)
                $res .= $columns[$name];
        } else {
            foreach ($columns as $name => $html)
                $res .= $html;
        }

        if ($modify) {
            $res .= '
   			<td class="data_text" style="white-space: nowrap;">
   				[ <a class="action_link" href="' . CURRENT_PAGE_URL . '/edit?id=' . $this->props['id'] . '">edit</a> ]
   			';
            if ($modify !== 'NO_DELETE')
                $res .= '[ <a class="action_link" onclick="return confirm(\'Are you sure?\');" href="' . CURRENT_PAGE_URL . '/edit?a=delete&id=' . $this->props['id'] . '">delete</a> ]';
            $res .= '</td>';
        }
        if (is_array($custom_link)) {
            $res .= '<td class="data_text"><a href="' . $custom_link['page'] . '?' . $custom_link['var'] . '=' . $this->props['id'] . '">' . $custom_link['title'] . '</a></td>';
        }
        return $res . '</tr>';
    }

    /**
     * @param bool $modify
     * @param array $swap
     * @param array $add
     * @param array $ignore
     * @param string $custom_link
     * @param string $row_style
     * @param array $column_order
     *
     * @return string
     */
    public function ToRow($modify = false, $swap = [], $add = [], $ignore = [], $custom_link = '', $column_order = [])
    {
        $res = '<tr>';
        $columns = [];
        if(is_null($swap)) {
            $swap = [];
        }

        if(is_null($add)) {
            $add = [];
        }

        if(is_null($ignore)) {
            $ignore = [];
        }

        foreach ($this->props as $name => $value)
            if (!in_array($name, $ignore)) {
                if (array_key_exists($name, $swap))
                    $value = $this->{$swap[$name]};
                else
                    $value = $this->ValueToNiceValue($name, $this->$name);


                if (!is_object($value)) {
                    if (is_array($value))
                        $value = implode(',', $value);
                    $columns[$name] = '<td>' . $value . '</td>';
                } else {
                    if ($value instanceof DateTime) {
                        $columns[$name] = '<td>' . Dates::Timestamp($value) . '</td>';
                    } else {
                        $columns[$name] = '<td><i>Object: </i>' . get_class($value) . '</td>';
                    }
                }

            }

        if (sizeof($add) > 0)
            foreach ($add as $header => $value) {
                if (is_array($value)) {
                    $name = $value['value'];
                    $value = $this->{$value['value']};
                } else {
                    $name = $value;
                    $value = $this->$value;
                }
                if (is_array($value)) {
                    $value = implode(',', $value);
                }

                $columns[$name] = '<td>' . $value . '</td>';
            }
        if (sizeof($column_order) > 0) {
            foreach ($column_order as $name)
                $res .= $columns[$name];
        } else {
            foreach ($columns as $name => $html)
                $res .= $html;
        }

        if ($modify) {
            $res .= '
   			<td class="data_text">
   				<a href="#"  onclick="' . get_class($this) . '.Load(' . $this->{static::$_primary[0]} . ');"><i class="fa fa-edit"></i></a>
   			</td>
   			';
        }
        if (is_array($custom_link)) {
            $res .= '<td class="data_text"><a href="' . $custom_link['page'] . '?' . static::$_primary[0] . '=' . $this->{static::$_primary[0]} . '">' . $custom_link['title'] . '</a></td>';
        }
        return $res . '</tr>';
    }

    /**
     * @return string
     */
    public function GetUUID()
    {
        $uuid = [];
        foreach (static::$_primary as $col) {
            if ($col) {
                $uuid[] = $col . ':' . $this->$col;
            }
        }
        return implode(',', $uuid);
    }

    // $trigger_change_log true is for FORM data to pass changes through set_property to trigger change log.
    // when coming from database, don't trigger change log
    // strict will halt when the hash passed in contains columns not in the table definition
    /**
     * @param      $row
     * @param bool $trigger_change_log
     */
    public function FromRow(&$row, $trigger_change_log = false, $strict = false)
    {
        global $User;

        if (is_null($trigger_change_log)) {
            $trigger_change_log = false;
        }
        if (is_null($strict)) {
            $strict = false;
        }

        $this->_from_db = true;
        $missing = [];

        foreach ($row as $name => $value) {
            if (property_exists(get_called_class(), $name)) {
                $this->$name = $value;
                continue;
            }

            if (!isset(static::$prop_definitions[$name])) {
                if ($strict) {
                    $missing[$name] = $value;
                }
                continue;
            }
            if (!is_null($User)) {
                if (static::$prop_definitions[$name]['type'] === 'datetime') {
                    if (!$value) {
                        $value = null;
                    } else {
                        if (strtotime($value)) {
                            $value = Dates::Timestamp(strtotime(Dates::Timestamp($value)) + $User->hours_diff * 3600);
                        }
                    }
                }
            }

            if ($trigger_change_log) {
                $this->$name = isset($row[$name]) ? $value : (!$trigger_change_log ? null : $value);
            } else {
                $this->props[$name] = isset($row[$name]) ? $value : (!$trigger_change_log ? null : $value);
            }
        }
        if ($strict && sizeof($missing)) {
            Halt(['error' => 'QuickDRY Error: Missing Columns', 'Object' => get_class($this), 'Columns' => $missing, 'Values' => $row]);
        }
    }

    /**
     * @param      $req
     * @param bool $save
     * @param bool $trigger_change_log
     *
     * @return bool
     */
    public function FromRequest(&$req, $save = true, $keep_existing_values = true)
    {
        foreach ($this->props as $name => $value) {
            $this->$name = isset($req[$name]) ? $req[$name] : (!$keep_existing_values ? null : $this->props[$name]);
        }

        if ($save) {
            return $this->Save();
        }
        return true;
    }

    // occasionally we end up reusing a selection over and over on the same page
    protected static $_select_cache = [];

    /**
     * @param        $selected
     * @param        $id
     * @param        $value
     * @param        $order_by
     * @param string $display
     * @param null $where
     * @param bool $show_none
     *
     * @return string
     */
    protected static function _Select($selected, $id, $value, $order_by, $display = "", $where = null, $show_none = true, $onchange = '')
    {
        if (is_null($show_none)) {
            $show_none = true;
        }

        $type = get_called_class();


        $hash = md5(serialize([$type, $order_by, $where]));
        if (!isset(static::$_select_cache[$hash])) {
            $items = $type::GetAll($where, is_array($order_by) ? $order_by : [$order_by => 'asc']);
            static::$_select_cache[$hash] = $items;
        } else
            $items = static::$_select_cache[$hash];

        $res = self::_SelectItems($items, $selected, $id, $value, $order_by, $display, $show_none, $onchange);
        return $res;
    }

    /**
     * @param        $items
     * @param        $selected
     * @param        $id
     * @param        $value
     * @param        $order_by
     * @param string $display
     * @param bool $show_none
     *
     * @return string
     */
    protected static function _SelectItems($items, $selected, $id, $value, $order_by, $display = "", $show_none = true, $onchange = '')
    {
        if (is_null($show_none)) {
            $show_none = true;
        }

        if (!is_array($id))
            $name = $id;
        else {
            $name = $id['name'];
            $id = $id['id'];
        }
        if ($display == "") {
            if (is_array($order_by)) {
                $display = array_keys($order_by)[0];
            } else {
                $display = $order_by;
            }
        }

        $select = "";

        if (is_array($selected)) {
            $select .= '<select class="form-control" onchange="' . $onchange . '" multiple size="' . (sizeof($items) + 1 <= 10 ? sizeof($items) + 1 : 10) . '" id="' . $id . '" name="' . $name . '[]">';
        } else {
            $select .= '<select class="form-control" onchange="' . $onchange . '"  id="' . $id . '"name="' . $name . '">';
        }

        if ($show_none) {
            $select .= '<option value="null">' . (is_bool($show_none) ? 'None' : $show_none) . '</option>' . "\r\n";
        }

        if (sizeof($items) > 0) {
            if (is_array($selected)) {
                foreach ($items as $item)
                    if (in_array($item->$value, $selected))
                        $select .= '<option selected="selected" value="' . $item->$value . '">' . $item->$display . '</option>\r\n';
                    else
                        $select .= '<option value="' . $item->$value . '">' . $item->$display . '</option>\r\n';
            } else {
                foreach ($items as $item)
                    if ($selected != $item->$value) // needs to be a loose comparison otherwise it doesn't work with numbers
                        $select .= '<option value="' . $item->$value . '">' . $item->$display . '</option>\r\n';
                    else
                        $select .= '<option selected="selected" value="' . $item->$value . '">' . $item->$display . '</option>\r\n';
            }
        }
        $select .= '</select>';
        return $select;
    }

    /**
     * @param        $selected
     * @param        $id
     * @param        $value
     * @param        $order_by
     * @param string $display
     * @param string $where
     *
     * @return string
     */
    protected static function _JQuerySelect($selected, $id, $value, $order_by = null, $display = "", $where = null)
    {
        $type = get_called_class();

        $items = $type::GetAll($where, $order_by);

        return self::_JQuerySelectItems($items, $selected, $id, $value, $display);

    }

    /**
     * @param        $items
     * @param        $selected
     * @param        $id
     * @param        $value
     * @param        $order_by
     * @param string $display
     *
     * @return string
     */
    protected static function _JQuerySelectItems($items, $selected, $id, $value, $display = "")
    {
        include_once 'controls/drag_select.js.php';

        if ($display == "")
            $display = $value;


        $remove = [];
        $add = [];

        if (sizeof($items) > 0) {
            if (is_array($selected)) {
                foreach ($items as $item)
                    if (in_array($item->$value, $selected))
                        $remove[$item->$value] = $item->$display;
                    else
                        $add[$item->$value] = $item->$display;
            } else {
                foreach ($items as $item)
                    if ($selected != $item->$value)
                        $add[$item->$value] = $item->$display;
                    else
                        $remove[$item->$value] = $item->$display;
            }
        }
        $not_selected = [];
        foreach ($add as $cid => $val)
            $not_selected[] = $cid;

        $html = '
		<div class="dds_panel">
<h2>Selected Items</h2>
<p>Select:
    <a href=\'#\' onclick=\'return $.dds.selectAll("list_' . $id . '_1");\'>all</a>
    <a href=\'#\' onclick=\'return $.dds.selectNone("list_' . $id . '_1");\'>none</a>
    <a href=\'#\' onclick=\'return $.dds.selectInvert("list_' . $id . '_1");\'>invert</a>
</p>

<ul class="dds" id="list_' . $id . '_1">
		';
        foreach ($remove as $item_id => $name)
            $html .= '<li class="dds" id="list_' . $id . '_1_item_' . $item_id . '">' . $name . '</li>';

        $html .= '
</ul>
<input type="hidden" name="modify_' . $id . '" value="1" />
<input type="hidden" name="' . $id . '" id=\'list_' . $id . '_1_serialised\' value="' . implode(',', $selected) . '" />
</div>
';

        $html .= '
		<div class="dds_panel">
<h2>Available Items</h2>
<p>Select:
    <a href=\'#\' onclick=\'return $.dds.selectAll("list_' . $id . '_2");\'>all</a>
    <a href=\'#\' onclick=\'return $.dds.selectNone("list_' . $id . '_2");\'>none</a>
    <a href=\'#\' onclick=\'return $.dds.selectInvert("list_' . $id . '_2");\'>invert</a>
</p>

<ul class="dds" id="list_' . $id . '_2">
		';
        foreach ($add as $item_id => $name)
            $html .= '<li class="dds" id="list_' . $id . '_2_item_' . $item_id . '">' . $name . '</li>';

        $html .= '
</ul>
<input type="hidden" name="' . $id . '_remaining" id=\'list_' . $id . '_2_serialised\' value="' . implode(',', $not_selected) . '" />
</div>
';

        $html .= '<div style="clear: both;"></div>';

        return $html;

    }

    /**
     * @param        $selected
     * @param        $id
     * @param        $value
     * @param        $order_by
     * @param string $display
     * @param string $where
     *
     * @return string
     */
    protected static function _EasySelect($selected, $id, $value, $order_by, $display = "", $where = '1=1')
    {
        $type = self::TableToClass(static::$DatabasePrefix, static::$table, static::$LowerCaseTable, static::$DatabaseTypePrefix);

        $items = [];
        eval("\$items = " . $type . "::GetAll(\"$order_by\",\"asc\",\"$where\");");

        return self::_EasySelectItems($items, $selected, $id, $value, $order_by, $display);
    }

    /**
     * @param        $items
     * @param        $selected
     * @param        $id
     * @param        $value
     * @param        $order_by
     * @param string $display
     *
     * @return string
     */
    protected static function _EasySelectItems($items, $selected, $id, $value, $order_by, $display = "")
    {
        if ($display == "")
            $display = $order_by;

        $select_remove = '<select multiple size="10" id="remove_' . $id . '[]"name="remove_' . $id . '[]"><option value="">None</option>';
        $select_add = '<select multiple size="10" id="add_' . $id . '[]"name="add_' . $id . '[]"><option value="">None</option>';

        if (sizeof($items) > 0) {
            if (is_array($selected)) {
                foreach ($items as $item)
                    if (in_array($item->$value, $selected))
                        $select_remove .= '<option value="' . $item->$value . '">' . $item->$display . '</option>\r\n';
                    else
                        $select_add .= '<option value="' . $item->$value . '">' . $item->$display . '</option>\r\n';
            } else {
                foreach ($items as $item)
                    if ($selected != $item->$value)
                        $select_add .= '<option value="' . $item->$value . '">' . $item->$display . '</option>\r\n';
                    else
                        $select_remove .= '<option value="' . $item->$value . '">' . $item->$display . '</option>\r\n';
            }
        }

        $select_remove .= '</select>';
        $select_add .= '</select>';

        return '<table><tr><td valign="top"><b>Current Items</b><br/>(select to remove)<br/>' . $select_remove . '</td><td>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</td><td valign="top"><b>Available Items</b><br/>(select to add)<br/>' . $select_add . '</td></tr></table>';

    }

}