<?php

define('SELECT_NO', 1);
define('SELECT_YES', 2);
/**
 * Class YesNoClass
 */
class YesNoClass extends FormClass
{
	protected static $_options = [
		'0' => 'Not Set',
		'2'	=> 'Yes',
		'1'	=> 'No',
    ];

    /**
     * @param        $selected
     * @param string $name
     *
     * @return string
     */
    public static function Select($selected, $name = 'yesno')
	{
		return self::SelectItems(self::$_options, $selected, $name);
	}	
}