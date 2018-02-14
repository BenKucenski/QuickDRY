<?php

class SafeClass
{
    private $_HaltOnError = true;
    private $_MissingProperties = [];

    public function HasMissingProperties()
    {
        return sizeof($this->_MissingProperties) > 0;
    }

    public function GetMissingPropeties()
    {
        return implode("\n", $this->_MissingProperties);
    }

    public function HaltOnError($true_or_false)
    {
        $this->_HaltOnError = $true_or_false ? true : false;
    }

    public function __get($name)
    {
        if ($this->_HaltOnError) {
            Halt('public $' . $name . '; is not a property of ' . get_class($this));
        } else {
            $this->_MissingProperties[] = 'public $' . $name . ';';
        }
        return null;
    }

    public function __set($name, $value)
    {
        if ($this->_HaltOnError) {
            Halt('public $' . $name . '; is not a property of ' . get_class($this));
        } else {
            $this->_MissingProperties[] = 'public $' . $name . ';';
        }
        return $value;
    }

    public function ToArray($ignore_empty = false)
    {
        $res = get_object_vars($this);
        if ($ignore_empty) {
            foreach ($res as $key => $val) {
                if (!$val) {
                    unset($res[$key]);
                }
                if ($key[0] == '_') {
                    unset($res[$key]);
                }
            }
        }
        return $res;
    }

    public function FromRow($row)
    {
        if (!is_array($row)) {
            Halt($row);
        }
        foreach ($row as $k => $v) {
            $this->$k = is_object($v) ? $v : fix_json($v);
        }
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
            Halt('Not an array or empty');
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
}