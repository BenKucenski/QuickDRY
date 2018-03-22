<?php

/**
 * Class Meta
 */
class Meta
{
	private static $title = null;
    private static $description = null;
    private static $keywords = null;

    /**
     * @param null $val
     * @return null|string
     */
    public static function Title($val = null) {
        if(is_null($val))
            return ': ' . str_replace('"','\\"',self::$title);
        self::$title = $val;
        return $val;
    }

    /**
     * @param null $val
     * @return mixed|null
     */
    public static function Description($val = null) {
        if(is_null($val))
            return str_replace('"','\\"',self::$description);
        self::$description = $val;
        return $val;
    }

    /**
     * @param null $val
     * @return mixed|null
     */
    public static function Keywords($val = null) {
        if(is_null($val))
            return str_replace('"','\\"',self::$keywords);
        self::$keywords = $val;
        return $val;
    }
}

