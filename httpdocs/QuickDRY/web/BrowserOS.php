<?php

/**
 * Class BrowserOS
 */
class BrowserOS
{
	public static $os = '';
	public static $browser = '';
	private static $is_mobile = '';

    /**
     * @return string
     */
    public static function IsMobile()
    {
        return static::$is_mobile;
    }

    /**
     *
     */
	public static function Configure()
	{
		$ua = isset($_SERVER["HTTP_USER_AGENT"]) ? $_SERVER["HTTP_USER_AGENT"] : '';
		if($ua == '') return;
		
		/* ==== Detect the OS ==== */
		
		// ---- Mobile ----
		
		// Android
		strpos($ua, 'Android') ? BrowserOS::$os = 'Android' : false;
		
		// BlackBerry
		strpos($ua, 'BlackBerry') ? BrowserOS::$os = 'BlackBerry' : false;
		
		// iPhone
		strpos($ua, 'iPhone') ? BrowserOS::$os = 'iPhone' : false;
		
		// Palm
		strpos($ua, 'Palm') ? BrowserOS::$os = 'Palm' : false;
		
		if(BrowserOS::$os != '')
			BrowserOS::$is_mobile = true;
		else 
		{
			// ---- Desktop ----
			
			// Linux
			strpos($ua, 'Linux') ? BrowserOS::$os = 'Linux' : false;
			
			// Macintosh
			strpos($ua, 'Macintosh') ? BrowserOS::$os = 'Macintosh' : false;
			
			// Windows
			strpos($ua, 'Windows') ? BrowserOS::$os = 'Windows' : false;

			BrowserOS::$is_mobile = false;
		}
		
		/* ============================ */
		
		
		/* ==== Detect the UA ==== */
		
		// Firefox
		strpos($ua, 'Firefox') ? BrowserOS::$browser = 'Firefox' : false; // All Firefox
		strpos($ua, 'Firefox/2.0') ? BrowserOS::$browser = 'Firefox/2.0' : false; // Firefox 2
		strpos($ua, 'Firefox/3.0') ? BrowserOS::$browser = 'Firefox/3.0' : false; // Firefox 3
		strpos($ua, 'Firefox/3.6') ? BrowserOS::$browser = 'Firefox/3.6' : false; // Firefox 3.6
		
		// Internet Exlporer
		strpos($ua, 'MSIE') ? BrowserOS::$browser = 'MSIE' : false; // All Internet Explorer
		strpos($ua, 'MSIE 7.0') ? BrowserOS::$browser = 'MSIE 7.0' : false; // Internet Explorer 7
		strpos($ua, 'MSIE 8.0') ? BrowserOS::$browser = 'MSIE 8.0' : false; // Internet Explorer 8
		
		// Opera
		$opera = preg_match("/\bOpera\b/i", $ua); // All Opera
		if($opera != '') BrowserOS::$browser = 'Opera';
		
		// Safari
		strpos($ua, 'Safari') ? BrowserOS::$browser = 'Safari' : false; // All Safari
		strpos($ua, 'Safari/419') ? BrowserOS::$browser = 'Safari/419' : false; // Safari 2
		strpos($ua, 'Safari/525') ? BrowserOS::$browser = 'Safari/525' : false; // Safari 3
		strpos($ua, 'Safari/528') ? BrowserOS::$browser = 'Safari/528' : false; // Safari 3.1
		strpos($ua, 'Safari/531') ? BrowserOS::$browser = 'Safari/531' : false; // Safari 4
		
		// Chrome - chrome lists safari as well so we need to check this last
		strpos($ua, 'Chrome') ? BrowserOS::$browser = 'Chrome' : false; // Google Chrome
		
		/* ============================ */
	}
}
