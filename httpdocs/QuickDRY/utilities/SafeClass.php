<?php
class SafeClass
{
    public function __get($name) {
       Halt('public $' . $name . '; is not a property of ' . get_class($this));
       return null;
     }

    public function __set($name, $value) {
        Halt('public $' . $name . '; is not a property of ' . get_class($this));
    }

    public function ToArray($ignore_empty = false)
    {
        $res = get_object_vars($this);
        if($ignore_empty) {
            foreach ($res as $key => $val) {
                if (!$val) {
                    unset($res[$key]);
                }
            }
        }
        return $res;
    }

    public function FromRow($row) {
        foreach($row as $k => $v) {
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
        if(!isset(static::$headers)) {
            Halt('You must define public static $headers in your class');
        }

        $objPHPExcel = new PHPExcel();
        $objPHPExcel->setActiveSheetIndex(0);
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
        $objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel5');
        $objWriter->save('php://output');
        exit;
    }
}
