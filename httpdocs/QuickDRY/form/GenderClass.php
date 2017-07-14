<?php

/**
 * Class GenderClass
 */
class GenderClass extends FormClass
{
	protected static $_options = [
		'm' => 'Male',
		'f'	=> 'Female',
    ];

    /**
     * @param       $selected
     * @param array $id
     *
     * @return string
     */
    public static function Select($selected, $id= ['name'=>'sex','id'=>'sex'])
	{
		return self::SelectItems(self::$_options, $selected, $id);
	}	
}