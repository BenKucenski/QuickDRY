<?php

/**
 * Class Cookie
 */
class Cookie
{
	private static $_VALS = [];

    /**
     * @param $name
     * @param $value
     */
    public function __set($name, $value)
	{
		setcookie($name,$value,0,'/',URL_DOMAIN);
		Cookie::$_VALS[$name] = $value;
	}

    /**
     * @param $name
     * @param $value
     * @param $expires
     */
    public static function Set($name, $value, $expires)
	{
		setcookie($name,$value,time() + $expires * 60 * 60,'/',URL_DOMAIN);
		Cookie::$_VALS[$name] = $value;
	}

    /**
     * @param $name
     *
     * @return null
     */
    public function __get($name)
	{
		if(isset(Cookie::$_VALS[$name]))
			return Cookie::$_VALS[$name];
		if(isset($_COOKIE[$name]))
			return $_COOKIE[$name];
		return NULL;
	}

    /**
     * @param $name
     *
     * @return bool
     */
    public function __isset($name)
	{
		return isset($_COOKIE[$name]);
	}

    /**
     * @param $name
     */
    public function __unset($name)
	{
		if(!isset($_COOKIE[$name]))
			return;
		
		// http://petersnotes.blogspot.com/2011/01/iphone-cookie-hell.html
		setcookie($name,''); // for iPhone
		setcookie($name,'',time() - 1,URL_DOMAIN);
		unset(Cookie::$_VALS[$name]);
		unset($_COOKIE[$name]);
	}
}
