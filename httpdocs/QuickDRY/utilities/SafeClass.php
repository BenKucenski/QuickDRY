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
     * @param $array
     * @param $filename
     *
     * pass in an array of SafeClass objects and the file name
     * note that public static $headers must be defined in the format
     * ['Nice Name' => 'Property Name']
     */
    public static function ToXLS($array, $filename)
    {
        if (!isset(static::$headers)) {
            Halt('You must define public static $headers in your class');
        }

        $objPHPExcel = new PHPExcel();
        try {
            $objPHPExcel->setActiveSheetIndex(0);
        } catch (Exception $ex) {
            Debug::Halt($ex);
        }
        $rowCount = 1;

        $column = 'A';

        foreach (static::$headers as $title => $property) {
            $objPHPExcel->getActiveSheet()->setCellValue($column . $rowCount, $title);
            $column++;
        }
        $rowCount++;
        foreach ($array as $item) {
            $column = 'A';
            foreach (static::$headers as $title => $property) {
                $objPHPExcel->getActiveSheet()->setCellValue($column . $rowCount, $item->$property);
                $column++;
            }
            $rowCount++;
        }

        header('Content-Type: application/vnd.ms-excel');
        header('Content-Disposition: attachment;filename="' . $filename . '"');
        header('Cache-Control: max-age=0');
        try {
            $objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel5');
            $objWriter->save('php://output');
        } catch (Exception $ex) {
            Debug::Halt($ex);
        }
        exit;
    }
}