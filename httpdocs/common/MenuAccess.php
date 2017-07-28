<?php
class MenuAccess
{
    private static $_MENU_ACCESS = [

    ];

    public static function GetForRole($RoleID)
    {
        $list = [];
        foreach(self::$_MENU_ACCESS as $url => $roles) {
            // use strict comparison since 0 is always true otherwise
            // https://stackoverflow.com/questions/13846769/php-in-array-0-value
            if(in_array($RoleID, $roles, true)) {
                $list[] = $url;
            }
        }
        return $list;
    }
}