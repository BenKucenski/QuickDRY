<?php

/**
 * Class SQL_Base
 */
class SQL_Base
{
    protected $props = [];
    protected static $table = null;

    protected $_change_log = [];
    protected $_history = null;
    protected $_from_db = null;
    protected $_smart_code = null;
    protected $_smart_location = null;

    /**
     * @param $database
     * @param $table
     *
     * @return string
     */
    public static function TableToClass($database, $table, $use_database, $lowercase_table)
    {
        if($lowercase_table) {
            $table = strtolower($table);
        }
        $database = strtolower($database);

        if($use_database) {
            $t = explode('_', $database . '_' . $table);
        } else {
            $t = explode('_', $table);
        }
        $type = '';
        foreach($t as $w)
            $type .= preg_replace('/[^a-z0-9]/si','',ucfirst($w));
        $type .= 'Class';
        if(is_numeric($type[0]))
            $type = 'i' . $type;
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
        return false;
        if(defined('DISABLE_CHANGE_LOG') && DISABLE_CHANGE_LOG) {
            return;
        }
        if(strcmp(static::$table,'change_log') == 0) // don't change log the change log
            return;

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
        $filename = $declaringClass->getFilename();

        $props = [];

        $fp = fopen($filename,'r');
        $code = fread($fp,filesize($filename));
        fclose($fp);
        $orig_code = $code;
        $pattern = '/@property\s+(.*?)[\r\n]/si';
        $matches = [];
        preg_match_all($pattern,$code,$matches);

        foreach($matches[1] as $var)
        {
            $parts = explode(' ', $var);
            $props[$parts[sizeof($parts) - 1]] = trim(str_replace($parts[sizeof($parts) - 1],'',$var));
        }


        $pattern = '/public function __get\(\$name\)(.*?)\n\t}/si';
        $matches = [];
        preg_match($pattern,$code,$matches);
        if(isset($matches[1]))
        {
            $code = $matches[1];
            $pattern = '/case \'(.*?)\':/si';
            $matches = [];
            preg_match_all($pattern,$code,$matches);
            if(isset($matches[1]))
            {
                $code = $matches[1];
                foreach($code as $get)
                {
                    if(!isset($props[$get]))
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
        foreach($props as $var => $type)
            $php .= ' * @property ' . $type . ' ' . $var . "\r\n";

        $php .= ' */';
        $code = preg_replace('/\<\?php\s+\/\*\*.*?\*\//si','',$orig_code);
        $code = $php . $code;

        $fp = fopen($filename,'w');
        fwrite($fp,$code);
        fclose($fp);
    }

    /**
     * @param $name
     *
     * @return int|null
     */
    public function __get($name)
    {
        switch($name)
        {
            case 'history':
                if(is_null($this->_history))
                    $this->_history = $this->_history();
                return $this->_history;

            default:
                return $this->get_property($name);
        }
    }

    /**
     * @param $name
     * @param $value
     *
     * @throws Exception
     */
    public function __set($name, $value)
    {
        switch($name)
        {
            default:
                $this->set_property($name,$value);
        }
        return $value;
    }

    /**
     * @return null
     */
    private function _history()
    {
        //$res = TbLogsChangeLogClass::GetAll('created_at','desc','uuid=' . self::Escape($this->GetUUID()) . ' AND table_name = ' . self::Escape(static::$table));
        //return $res;
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
        foreach(static::$prop_definitions as $name => $def)
            $cols[] = $name;
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
    public static function GetAll($where = null, $order_by = null, $limit = null )
    {
        if(!is_null($order_by) && !is_array($order_by))
            Halt('$order_by must be an assoc array ("col"=>"asc,desc",...)', true);

        if(!is_null($where) && !is_array($where))
            Halt('$where must be an assoc array ("col"=>"val",...) ', true);

        if(!is_null($order_by)) {
            foreach ($order_by as $col => $dir) {
                if (!self::check_props(trim($col))) {
                    Halt($col . ' is not a valid order by column for ' . get_called_class());
                    return null;
                }
            }
        }

        if(is_array($where) && sizeof($where) == 0) {
            $where = null;
        }

        if(!is_null($where)) {
            foreach ($where as $col => $dir) {
                if (!self::check_props(trim($col))) {
                    Halt($col . ' is not a valid where column for ' . get_called_class());
                    return null;
                }
            }
        }

        return static::_GetAll($where, $order_by, $limit);
    }

    /**
     * @param string $sql_where
     *
     * @return mixed
     */
    public static function GetCount($sql_where = '1=1')
    {
        return static::_GetCount($sql_where);
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
     * @return null
     */
    public static function GetAllPaginated($where = null, $order_by = null, $page = 0, $per_page = 0, $left_join = null, $limit = null )
    {
        return static::_GetAllPaginated($where, $order_by, $page, $per_page, $left_join, $limit);
    }

    /**
     * @return array
     */
    public static function GetVars()
    {
        $vars = [];
        foreach(static::$prop_definitions as $name => $def)
            $vars[$name] = null;
        return $vars;
    }

    /**
     * @param null $name
     *
     * @return mixed
     */
    protected function get_property( /*string*/ $name = null)
    {
        if(array_key_exists($name, $this->props))
        {
            return $this->props[$name];
        }
        Halt($name . ' is not a property of ' . get_class($this) . "\r\n");
    }

    public function ClearProps() {
        foreach($this->props as $n => $v) {
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
        return ToArray($this->props);
    }

    /**
     * @param $name
     * @param $value
     *
     * @throws Exception
     */
    protected function set_property($name, $value)
    {
        if(is_array($value)) {
            Halt(['properties must be string or number', $value]);
        }

        if(is_object($value)) {
            Halt(['properties must be string or number', $value]);
        }

        if(strcasecmp($value,'null') == 0) {
            $value = null;
        }

        if(array_key_exists($name, $this->props))
        {
            $old_val = $this->StrongType($name, $this->props[$name]);
            $new_val = $this->StrongType($name, $value);

            $changed = false;
            if(is_null($old_val) && !is_null($new_val))
                $changed = true;
            else
                if(!is_null($old_val) && is_null($new_val))
                    $changed = true;
                else
                    if(is_numeric($old_val) && is_numeric($new_val) && $new_val != $old_val)
                        $changed = true;
                    else
                        if(strcmp($new_val,$old_val) != 0)
                            $changed = true;

            if($changed)
            {
                if(is_null($new_val))
                    $new_val = 'null';
                if(is_null($old_val));
                $old_Val = 'null';
                $this->_change_log[$name] = ['new'=>$new_val,'old'=>$old_val];
            }

            $this->props[$name] = $value;
        }
        else
            throw new Exception($name . ' is not a property of ' . get_class($this) . '<pre>Object<br/>' . debug_backtrace() . '</pre>');
    }

    /**
     * @param string $sort_by
     * @param string $dir
     * @param bool   $modify
     * @param array  $add
     * @param array  $ignore
     * @param string $add_params
     * @param bool   $sortable
     * @param array  $column_order
     *
     * @return string
     */
    public static function GetHeader($sort_by = '', $dir = '', $modify = false, $add = [], $ignore = [], $add_params = '', $sortable = true, $column_order = [])
    {
        return static::_GetHeader(static::$prop_definitions, $sort_by, $dir, $modify, $add, $ignore, $add_params, $sortable, $column_order, $column_order);
    }

    /**
     * @param string $sort_by
     * @param string $dir
     * @param bool   $modify
     * @param array  $add
     * @param array  $ignore
     * @param string $add_params
     * @param bool   $sortable
     * @param array  $column_order
     *
     * @return string
     */
    public static function GetBareHeader($sort_by = '', $dir = '', $modify = false, $add = [], $ignore = [], $add_params = '', $sortable = true, $column_order = [])
    {
        return static::_GetBareHeader(static::$prop_definitions, $sort_by, $dir, $modify, $add, $ignore, $add_params, $sortable, $column_order, $column_order);
    }

    /**
     * @param        $props
     * @param        $sort_by
     * @param        $dir
     * @param bool   $modify
     * @param array  $add
     * @param array  $ignore
     * @param string $add_params
     * @param bool   $sortable
     * @param array  $column_order
     *
     * @return string
     */
    protected static function _GetHeader(&$props, $sort_by, $dir, $modify = false, $add = [], $ignore = [], $add_params = '', $sortable = true, $column_order = [])
    {
        $not_dir = $dir == 'asc' ? 'desc' : 'asc';
        $arrow = $dir == 'asc' ? '&uarr;' : '&darr;';

        $columns = [];

        foreach($props as $name => $info)
            if(!in_array($name, $ignore))
                if($sortable)
                    $columns[$name] = '<th><a href="' .  CURRENT_PAGE . '?sort_by=' . $name . '&dir=' . (strcasecmp($sort_by, $name) == 0 ? $not_dir : 'asc') . '&per_page=' . PER_PAGE . '&' . $add_params . '">' . static::ColumnNameToNiceName($name) . '</a>' . (strcasecmp($sort_by, $name) == 0 ? ' ' . $arrow : ''). '</th>';
                else
                    $columns[$name] = '<th>' . static::ColumnNameToNiceName($name) . '</th>';

        if(sizeof($add) > 0)
            foreach($add as $header=>$value)
            {
                if(is_array($value) && $sortable)
                    $columns[$value['value']] = '<th><a href="' .  CURRENT_PAGE . '?sort_by=' . $value['sort_by'] . '&dir=' . ($sort_by == $value['sort_by'] ? $not_dir : 'asc') . '&per_page=' . PER_PAGE . '&' . $add_params . '">' . $header . '</a>' . ($sort_by == $value['sort_by'] ? ' ' . $arrow : ''). '</th>';
                else
                {
                    if(is_array($value))
                        $columns[$value['value']] = '<th>' . $header . '</th>';
                    else
                        $columns[$value] = '<th>' . $header . '</th>';
                }
            }

        $res = '<thead><tr>';
        if(sizeof($column_order) > 0)
        {
            foreach($column_order as $order)
                $res .= $columns[$order];
        }
        else
            foreach($columns as $column)
                $res .= $column;

        if($modify)
            $res .= '<th>Action</th>';

        return $res . '</tr></thead>';
    }

    /**
     * @param        $props
     * @param        $sort_by
     * @param        $dir
     * @param bool   $modify
     * @param array  $add
     * @param array  $ignore
     * @param string $add_params
     * @param bool   $sortable
     * @param array  $column_order
     *
     * @return string
     */
    protected static function _GetBareHeader(&$props, $sort_by, $dir, $modify = false, $add = [], $ignore = [], $add_params = '', $sortable = true, $column_order = [])
    {
        $not_dir = $dir == 'asc' ? 'desc' : 'asc';
        $arrow = $dir == 'asc' ? '&uarr;' : '&darr;';

        $columns = [];

        foreach($props as $name => $info)
            if(!in_array($name, $ignore))
                if($sortable)
                    $columns[$name] = '<th>' . $info['display'] . '</th>' . "\r\n";
                else
                    $columns[$name] = '<th>' . $info['display'] . '</th>' . "\r\n";

        if(sizeof($add) > 0)
            foreach($add as $header=>$value)
            {
                if(is_array($value) && $sortable)
                    $columns[$value['value']] = '<th>' . $header . '</th>' . "\r\n";
                else
                {
                    if(is_array($value))
                        $columns[$value['value']] = '<th>' . $header . '</th>' . "\r\n";
                    else
                        $columns[$value] = '<th>' . $header . '</th>' . "\r\n";
                }
            }

        $res = '<thead><tr>';
        if(sizeof($column_order) > 0)
        {
            foreach($column_order as $order)
                $res .= $columns[$order];
        }
        else
            foreach($columns as $column)
                $res .= $column;

        if($modify)
            $res .= '<th>Action</th>' . "\r\n";

        return $res . '</tr></thead>';
    }

    /**
     * @param bool   $modify
     * @param array  $swap
     * @param array  $add
     * @param array  $ignore
     * @param string $custom_link
     * @param string $row_style
     * @param array  $column_order
     *
     * @return string
     */
    public function ToRowLegacy($modify = false, $swap = [], $add = [], $ignore = [], $custom_link = '', $row_style ='', $column_order = [])
    {
        $res = '<tr style="' . $row_style . '">';
        $columns = [];

        foreach($this->props as $name => $value)
            if(!in_array($name, $ignore))
            {
                if(array_key_exists($name, $swap))
                    $value = $this->$swap[$name];
                else
                    $value = $this->$name;



                if(is_array($value))
                    $value = implode(',',$value);
                $class = 'data_text';
                if(is_numeric(str_replace('-','',$value)))
                    $class = 'data_num';

                $columns[$name] = '<td class="' . $class . '">' . $value . '</td>';
            }

        if(sizeof($add) > 0)
            foreach($add as $header=>$value)
            {
                if(is_array($value))
                {
                    $name = $value['value'];
                    $value = $this->$value['value'];
                }
                else
                {
                    $name = $value;
                    $value = $this->$value;
                }
                if(is_array($value))
                    $value = implode(',',$value);
                $class = 'data_text';
                if(is_numeric(str_replace('-','',$value)))
                    $class = 'data_num';

                $columns[$name] = '<td class="' . $class . '">' . $value . '</td>';
            }
        if(sizeof($column_order) > 0)
        {
            foreach($column_order as $name)
                $res .= $columns[$name];
        }
        else
        {
            foreach($columns as $name => $html)
                $res .= $html;
        }

        if($modify)
        {
            $res .= '
   			<td class="data_text" style="white-space: nowrap;">
   				[ <a class="action_link" href="' . CURRENT_PAGE_URL . '/edit?id=' . $this->props['id'] . '">edit</a> ]
   			';
            if($modify !== 'NO_DELETE')
                $res .= '[ <a class="action_link" onclick="javascript:return confirm(\'Are you sure?\');" href="' . CURRENT_PAGE_URL . '/edit?a=delete&id=' . $this->props['id'] . '">delete</a> ]';
            $res .= '</td>';
        }
        if(is_array($custom_link))
        {
            $res .= '<td class="data_text"><a href="' . $custom_link['page'] . '?' . $custom_link['var'] . '=' . $this->props['id'] . '">' . $custom_link['title'] . '</a></td>';
        }
        return $res . '</tr>';
    }

    /**
     * @param bool   $modify
     * @param array  $swap
     * @param array  $add
     * @param array  $ignore
     * @param string $custom_link
     * @param string $row_style
     * @param array  $column_order
     *
     * @return string
     */
    public function ToRow($modify = false, $swap = [], $add = [], $ignore = [], $custom_link = '', $row_style ='', $column_order = [])
    {
        $res = '<tr style="' . $row_style . '">';
        $columns = [];

        foreach($this->props as $name => $value)
            if(!in_array($name, $ignore))
            {
                if(array_key_exists($name, $swap))
                    $value = $this->$swap[$name];
                else
                    $value = $this->ValueToNiceValue($name, $this->$name);


                $class = 'data_text';
                if(!is_object($value))
                {
                    if(is_array($value))
                        $value = implode(',',$value);
                    if(is_numeric(str_replace('-','',$value)))
                        $class = 'data_num';
                    $columns[$name] = '<td class="' . $class . '">' . $value . '</td>';
                }

            }

        if(sizeof($add) > 0)
            foreach($add as $header=>$value)
            {
                if(is_array($value))
                {
                    $name = $value['value'];
                    $value = $this->$value['value'];
                }
                else
                {
                    $name = $value;
                    $value = $this->$value;
                }
                if(is_array($value))
                    $value = implode(',',$value);
                $class = 'data_text';
                if(is_numeric(str_replace('-','',$value)))
                    $class = 'data_num';

                $columns[$name] = '<td class="' . $class . '">' . $value . '</td>';
            }
        if(sizeof($column_order) > 0)
        {
            foreach($column_order as $name)
                $res .= $columns[$name];
        }
        else
        {
            foreach($columns as $name => $html)
                $res .= $html;
        }

        if($modify)
        {
            $res .= '
   			<td class="data_text">
   				<a href="#"  onclick="' . get_class($this) . '.Load(' . $this->{static::$_primary[0]} . ');"><i class="fa fa-edit"></i></a>
   			</td>
   			';
        }
        if(is_array($custom_link))
        {
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
        foreach(static::$_primary as $col) {
            if ($col) {
                $uuid[] = $col . ':' . $this->$col;
            }
        }
        return implode(',',$uuid);
    }

    // overwrite false is for FORM data so pass changes through set_property to trigger change log
    // when coming from database, don't trigger change log
    /**
     * @param      $row
     * @param bool $overwrite
     */
    public function FromRow(&$row, $overwrite = true)
    {
        global $User;

        $this->_from_db = true;

        foreach($row as $name => $value) {
            if(property_exists(get_called_class(), $name)) {
                $this->$name = $value;
                continue;
            }

            if(!isset(static::$prop_definitions[$name])) {
               continue;
            }
            if(!is_null($User)) {
                if (static::$prop_definitions[$name]['type'] === 'datetime') {
                    if (!$value) {
                        $value = null;
                    } else {
                        if (strtotime($value)) {
                            $value = Timestamp(strtotime(Timestamp($value)) + $User->hours_diff * 3600);
                        }
                    }
                }
            }

            if ($overwrite) {
                $this->props[$name] = isset($row[$name]) ? $value : ($overwrite ? null : $value);
            } else {
                $this->$name = isset($row[$name]) ? $value : ($overwrite ? null : $value);
            }
        }
    }

    /**
     * @param      $req
     * @param bool $save
     * @param bool $overwrite
     *
     * @return bool
     */
    public function FromRequest(&$req, $save = true, $overwrite = false)
    {
        foreach($this->props as $name => $value) {
            $this->$name = isset($req[$name]) ? $req[$name] : ($overwrite ? null : $this->props[$name]);
        }

        if($save) {
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
     * @param null   $where
     * @param bool   $show_none
     *
     * @return string
     */
    protected static function _Select($selected, $id, $value, $order_by, $display = "", $where = null, $show_none = true, $onchange = '')
    {
        if(is_null($show_none)) {
            $show_none = true;
        }

        $type = get_called_class();


        $hash = md5(serialize([$type, $order_by, $where]));
        if(!isset(static::$_select_cache[$hash]))
        {
            $items = $type::GetAll($where, [$order_by=>'asc']);
            static::$_select_cache[$hash] = $items;
        }
        else
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
     * @param bool   $show_none
     *
     * @return string
     */
    protected static function _SelectItems($items, $selected, $id, $value, $order_by, $display = "", $show_none = true, $onchange = '')
    {
        if(is_null($show_none)) {
            $show_none = true;
        }

        if(!is_array($id))
            $name = $id;
        else
        {
            $name = $id['name'];
            $id = $id['id'];
        }
        if($display == "")
            $display = $order_by;

        $select = "";

        if(is_array($selected))
            $select .= '<select class="form-control" onchange="' . $onchange. '" multiple size="' . (sizeof($items)+1 <= 10 ? sizeof($items)+1 : 10 ) .'" id="' . $id . '" name="' . $name . '[]">';
        else
            $select .= '<select class="form-control" onchange="' . $onchange. '"  id="' . $id . '"name="' . $name . '">';

        if($show_none)
            $select .= '<option value="null">' . ($show_none == true ? 'None' : $show_none) . '</option>' . "\r\n";

        if(sizeof($items) > 0)
        {
            if(is_array($selected))
            {
                foreach($items as $item)
                    if(in_array($item->$value, $selected))
                        $select .= '<option selected="selected" value="' . $item->$value . '">' . $item->$display . '</option>\r\n';
                    else
                        $select .= '<option value="' . $item->$value . '">' . $item->$display . '</option>\r\n';
            }
            else
            {
                foreach($items as $item)
                    if($selected !== $item->$value)
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

        return self::_JQuerySelectItems($items, $selected, $id, $value, $order_by, $display);

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
    protected static function _JQuerySelectItems($items, $selected, $id, $value, $order_by, $display = "")
    {
        include_once 'controls/drag_select.js.php';

        if($display == "")
            $display = $value;


        $remove = [];
        $add = [];

        if(sizeof($items) > 0)
        {
            if(is_array($selected))
            {
                foreach($items as $item)
                    if(in_array($item->$value, $selected))
                        $remove[$item->$value] = $item->$display;
                    else
                        $add[$item->$value] = $item->$display;
            }
            else
            {
                foreach($items as $item)
                    if($selected != $item->$value)
                        $add[$item->$value] = $item->$display;
                    else
                        $remove[$item->$value] = $item->$display;
            }
        }
        $not_selected = [];
        foreach($add as $cid => $val)
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
        foreach($remove as $item_id => $name)
            $html .= '<li class="dds" id="list_' . $id . '_1_item_' . $item_id . '">' . $name . '</li>';

        $html .= '
</ul>
<input type="hidden" name="modify_' . $id . '" value="1" />
<input type="hidden" name="' . $id . '" id=\'list_' . $id . '_1_serialised\' value="' . implode(',',$selected) . '" />
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
        foreach($add as $item_id => $name)
            $html .= '<li class="dds" id="list_' . $id . '_2_item_' . $item_id . '">' . $name . '</li>';

        $html .= '
</ul>
<input type="hidden" name="' . $id . '_remaining" id=\'list_' . $id . '_2_serialised\' value="' . implode(',',$not_selected) . '" />
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
        $type = self::TableToClass(static::$database, static::$table, static::$UseDatabase, static::$LowerCaseTable);

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
        if($display == "")
            $display = $order_by;

        $select_remove = '<select multiple size="10" id="remove_' . $id . '[]"name="remove_' . $id . '[]"><option value="">None</option>';
        $select_add = '<select multiple size="10" id="add_' . $id . '[]"name="add_' . $id . '[]"><option value="">None</option>';

        if(sizeof($items) > 0)
        {
            if(is_array($selected))
            {
                foreach($items as $item)
                    if(in_array($item->$value, $selected))
                        $select_remove .= '<option value="' . $item->$value . '">' . $item->$display . '</option>\r\n';
                    else
                        $select_add .= '<option value="' . $item->$value . '">' . $item->$display . '</option>\r\n';
            }
            else
            {
                foreach($items as $item)
                    if($selected != $item->$value)
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