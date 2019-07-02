<?php

/**
 * Class MonthClass
 */
class MonthClass extends FormClass
{
	protected static $_options = [
		'null' => 'Not Set',
		'1' => 'January',
		'2' => 'February',
		'3' => 'March',
		'4' => 'April',
		'5' => 'May',
		'6' => 'June',
		'7' => 'July',
		'8' => 'August',
		'9' => 'September',
		'10' => 'October',
		'11' => 'November',
		'12' => 'December',
    ];

    /**
     * @param $id
     *
     * @return null
     */
    public static function Get($id)
	{
		return isset(self::$_options[$id]) ? self::$_options[$id] : null;
	}

    /**
     * @param       $selected
     * @param array $id
     *
     * @return string
     */
    public static function Select($selected, $id= ['name'=>'month','id'=>'month'])
	{
		return self::SelectItems(self::$_options, $selected, $id, 'form-control');
		
	}	
}