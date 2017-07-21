<?php
class SafeClass
{
    public function __get($name) {
       Debug::Halt('public $' . $name . '; is not a property of ' . get_class($this));
       return null;
     }

    public function __set($name, $value) {
        Debug::Halt('public $' . $name . '; is not a property of ' . get_class($this));
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
            $this->$k = is_object($v) ? $v : StringManip::FixJSON($v);
        }
    }
}
