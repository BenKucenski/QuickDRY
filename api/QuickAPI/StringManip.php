<?php
class StringManip extends SafeClass
{
    public static function FixJSON($json)
    {
        if(!is_array($json)) {
            return iconv('UTF-8', 'UTF-8//IGNORE', utf8_encode($json));
        }

        foreach ($json as $i => $row) {
            if (is_array($row)) {
                $json[$i] = self::FixJSON($row);
            } else {
                if(is_object($json[$i])) {
                    if($json[$i] instanceof DateTime){
                        $json[$i] = Date::Datestamp($json[$i]);
                    } else {
                        Debug::Halt($json[$i]);
                    }
                }
                $json[$i] = iconv('UTF-8', 'UTF-8//IGNORE', utf8_encode($json[$i]));
            }
        }
        return $json;
    }

    public static function FormFilter($value)
    {
        return str_replace('"','\\"', $value);
    }

}