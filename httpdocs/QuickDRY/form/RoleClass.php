<?php
/**
 * Class YesNoClass
 */
class RoleClass extends FormClass
{
    protected static $_options = [
        '' => 'Not Set',
        'Administrator'	=> 'Site Admin',
    ];

    /**
     * @param        $selected
     * @param string $name
     *
     * @return string
     */
    public static function Select($selected, $name = 'role')
    {
        return self::SelectItems(self::$_options, $selected, $name);
    }
}