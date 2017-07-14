<?php
class Meta
{
	private static $title = null;
    private static $description = null;
    private static $keywords = null;

    public static function Title($val = null) {
        if(is_null($val))
            return ': ' . str_replace('"','\\"',self::$title);
        self::$title = $val;
        return $val;
    }

    public static function Description($val = null) {
        if(is_null($val))
            return str_replace('"','\\"',self::$description);
        self::$description = $val;
        return $val;
    }

    public static function Keywords($val = null) {
        if(is_null($val))
            return str_replace('"','\\"',self::$keywords);
        self::$keywords = $val;
        return $val;
    }
}

