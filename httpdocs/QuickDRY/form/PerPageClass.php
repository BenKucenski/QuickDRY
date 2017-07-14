<?php

/**
 * Class PerPageClass
 */
class PerPageClass extends FormClass
{
    protected static $_options = [
        20 => '20',
        50 => '50',
        100	=> '100',
        250 => '250',
    ];

    /**
     * @param       $selected
     * @param array $id
     *
     * @return string
     */
    public static function Select($selected, $id= ['name'=>'per_page','id'=>'per_page'], $onchange = '')
    {
        return self::SelectItems(self::$_options, $selected, $id, $onchange);
    }
}