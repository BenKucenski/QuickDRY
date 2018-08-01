<?php

/**
 * Class Metrics
 */
class Metrics
{
    private static $_vars = [];
    private static $_count = [];
    private static $_total = [];
    private static $_running = [];

    private static $global_start = 0;

    /**
     *
     */
    public static function StartGlobal()
    {
        static::$global_start = microtime(true);
    }

    /**
     * @return int|mixed
     */
    public static function GetGlobal()
    {
        return microtime(true) - static::$global_start;
    }

    /**
     * @return string
     */
    public static function ToString($show_total = true)
    {
        $res = "individual task time (secs)\r\n";
        $res .= "--------------------\r\n";
        $total = 0;
        foreach (static::$_vars as $name => $last) {
            if (!isset(static::$_total[$name])) {
                static::$_total[$name] = 0;
            }

            if (!isset(static::$_count[$name])) {
                static::$_count[$name] = 0;
            }

            $res .= "$name: " . static::$_count[$name] . " @ " . (static::$_count[$name] && static::$_total[$name] ? static::$_total[$name]
                    / static::$_count[$name] : 0) . "secs\r\n";
            $total += static::$_total[$name];
        }
        $res .= "\r\ntime spent per task (secs)\r\n";
        $res .= "--------------------\r\n";
        foreach (static::$_vars as $name => $last) {
            $res .= "$name: " . static::$_total[$name] . " (" . number_format(static::$_total[$name] * 100 / $total, 2) . "%)\r\n";
        }
        if (sizeof(self::$_running)) {
            $res .= "Still Running\r\n";
            foreach (static::$_running as $name => $value) {
                $res .= "$name: \r\n";
            }
        }
        if ($show_total) {
            $res .= "total time: $total\r\n\r\n";
        }

        return $res;
    }

    /**
     * @param $name
     */
	public static function Toggle($name)
    {
        if(isset(self::$_running[$name])) {
            self::Stop($name);
        } else {
            self::Start($name);
        }
    }

    /**
     * @param $name
     */
    public static function Start($name)
	{
	    if(isset(self::$_running[$name])) {
	        return;
        }

	    self::$_running[$name] = true;
		static::$_vars[$name] = microtime(true);
	}

    /**
     * @param $name
     */
    public static function Stop($name)
	{
	    if(!isset(self::$_running[$name])) {
	        return;
        }

		if(!isset(static::$_count[$name]))
			static::$_count[$name] = 0;
		if(!isset(static::$_total[$name]))
			static::$_total[$name] = 0;

		static::$_vars[$name] = microtime(true) - static::$_vars[$name];
		static::$_count[$name]++;
		static::$_total[$name] += static::$_vars[$name];
        unset(self::$_running[$name]);
	}

    public static function Reset() {
        static::$_vars = [];
        static::$_count = [];
        static::$_total = [];
    }
}

