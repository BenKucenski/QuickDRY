<?php

/**
 * Class MonthClass
 */
class MonthClass
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
		$res = '<select id="' . $id['id'] . '" name="' . $id['name'] . '">';
		foreach(self::$_options as $id => $disp)
		{
			if(strcasecmp($id,$selected) == 0)
				$res .= '<option selected value="' . $id . '">' . $disp . '</input>';
			else
				$res .= '<option value="' . $id . '">' . $disp . '</input>';
		}
		$res .= '</select>';
		return $res;
		
	}	
}