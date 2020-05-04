<?php

/**
 * Class SafeClass
 */
class SafeClass
{
    private $_HaltOnError = true;
    private $_MissingProperties = [];
    private $_Aliases = [];

    /**
     * @return bool
     */
    public function HasMissingProperties()
    {
        return sizeof($this->_MissingProperties) > 0;
    }

    /**
     * @return string
     */
    public function GetMissingPropeties()
    {
        return implode("\n", $this->_MissingProperties);
    }

    /**
     * @param $true_or_false
     */
    public function HaltOnError($true_or_false)
    {
        $this->_HaltOnError = $true_or_false ? true : false;
    }

    /**
     * @param $name
     * @return null
     */
    public function __get($name)
    {
        if ($this->_HaltOnError) {
            Halt('QuickDRY Error: public $' . $name . '; is not a property of ' . get_class($this));
        } else {
            $this->_MissingProperties[$name] = 'public $' . $name . ';';
        }
        return null;
    }

    /**
     * @param $name
     * @param $value
     * @return mixed
     */
    public function __set($name, $value)
    {
        if ($this->_HaltOnError) {
            Halt('QuickDRY Error: public $' . $name . '; is not a property of ' . get_class($this));
        } else {
            $this->_MissingProperties[$name] = 'public $' . $name . ';';
        }
        return $value;
    }

    /**
     * @param bool $ignore_empty
     * @return array
     */
    public function ToArray($ignore_empty = false)
    {
        $res = get_object_vars($this);
        foreach ($res as $key => $val) {
            if ($ignore_empty) {
                if (!$val) {
                    unset($res[$key]);
                }
            }
            if ($key[0] == '_') {
                unset($res[$key]);
            }
        }

        foreach ($res as $key => $val) {
            if ($val instanceof DateTime) {
                $res[$key] = Dates::Timestamp($val);
            }
        }
        return $res;
    }

    /**
     * @param $row
     */
    public function FromRow($row, $convert_objects = false)
    {
        $halt_on_error = $this->_HaltOnError;

        $this->HaltOnError(false);
        if (!is_array($row)) {
            Halt($row);
        }
        foreach ($row as $k => $v) {
            if($convert_objects && is_object($v)) {
                $v = Dates::Timestamp($v);
            }

            $a = preg_replace('/[^a-z0-9_]/si','', $k);
            if($a != $k) {
                $this->_Aliases['_' . $a] = $k;
                $k = '_' . $a;
            }
            $this->$k = is_array($v) || is_object($v) ? $v : Strings::FixJSON($v);
        }
        if($this->HasMissingProperties()) {
            if($halt_on_error) {
                Halt($this->GetMissingPropeties());
            }
        }
        $this->HaltOnError($halt_on_error);
    }

    /**
     * @param StdClass[] $array
     * @param $filename
     *
     * pass in an array of SafeClass objects and the file name
     */
    public static function ToCSV($array, $filename, $headers = null)
    {
        if (!is_array($array) || !sizeof($array)) {
            Halt('QuickDRY Error: Not an array or empty');
        }

        $header = $headers ? $headers : array_keys(get_object_vars($array[0]));

        $output = fopen("php://output", 'w') or die("Can't open php://output");
        header("Content-Type:application/csv");
        header("Content-Disposition:attachment;filename=\"" . $filename . "\"");
        fputcsv($output, $header);
        foreach ($array as $item) { /* @var $item SafeClass */
            $ar = array_values(get_object_vars($item));
            fputcsv($output, $ar);
        }
        fclose($output) or die("Can't close php://output");
        exit;
    }

    /**
     * @param SafeClass[] $items
     * @return string
     */
    public static function ToHTML(&$items, $class = '', $style = '', $numbered = false, $limit = 0)
    {
        if(!sizeof($items)) {
            return '';
        }

        $obj_class = get_called_class();
        $cols = array_keys(get_object_vars($items[0]));

        $se = new SimpleExcel();
        $se->Report = $items;
        $se->Title = $obj_class;
        $se->Columns = [];
        foreach ($cols as $col) {
            $se->Columns[$col] = new SimpleExcel_Column(null, $col);
        }

        $html = '<table class="' . $class . '" style="' . $style . '"><thead><tr>';
        if ($numbered) {
            $html .= '<th></th>';
        }
        foreach ($se->Columns as $col => $settings) {
            $html .= '<th>' . $col . '</th>';
        }
        $html .= '</tr></thead><tbody>';
        foreach ($se->Report as $i => $item) {
            if($limit && $i >= $limit) {
                break;
            }
            $html .= '<tr>';
            if ($numbered) {
                $html .= '<td>' . ($i + 1) . '</td>';
            }
            foreach ($se->Columns as $col => $settings) {
                if(is_array($item->$col)) {
                    continue;
                }
                if (is_object($item->$col)) {
                    $html .= '<td>' . Dates::Datestamp($item->$col) . '</td>';
                } else {
                    $html .= '<td>' . ($item->$col) . '</td>';
                }
            }
            $html .= '</tr>';
        }

        return $html . '</tbody></table>';
    }
}