<?php

/**
 * Class Session
 */
class Session
{
	static $_VALS = [];

    public function GetVals()
    {
        $vals = [];
        foreach ($_SESSION as $k => $v) {
            $vals[$k] = $v;
        }
        foreach (self::$_VALS as $k => $v) {
            $vals[$k] = $v;
        }

        return $vals;
    }

	public static function ClearAll()
	{
		foreach($_SESSION as $n => $v)
		{
			unset(static::$_VALS[$n]);
			unset($_SESSION[$n]);
		}
	}

    /**
     * @param $name
     *
     * @return mixed|string
     */
    public static function Get($name) {
        if(isset($_SESSION[$name]))
            return unserialize($_SESSION[$name]);

        if(isset(static::$_VALS[$name]))
            return unserialize(static::$_VALS[$name]);

        return '';
    }

    /**
     * @param $name
     *
     * @return mixed|string
     */
    public function __get($name)
	{
        return static::Get($name);
	}

    /**
     * @param $name
     */
    public static function Clear($name = null)
    {
        if(is_null($name)) {
            session_destroy();
        }
        if(isset($_SESSION[$name]))
        {
            unset(static::$_VALS[$name]);
            unset($_SESSION[$name]);
        }
    }

    /**
     * @param $name
     */
    public function __unset($name)
	{
        return static::Clear($name);
	}

    /**
     * @param $name
     * @param $value
     */
    public static function Set($name, $value)
    {
        $_SESSION[$name] = serialize($value);
        static::$_VALS[$name] = $_SESSION[$name];
    }

    /**
     * @param $name
     * @param $value
     */
    public function __set($name, $value)
	{
        return static::Set($name, $value);
	}

    /**
     * @param $name
     *
     * @return bool
     */
    public static function Check($name)
    {
        return isset($_SESSION[$name]);
    }

    /**
     * @param $name
     *
     * @return bool
     */
    public function __isset($name)
	{
		return static::Check($name);
	}
}

$Session = new Session();
