<?php

/**
 * Class MenuAccess
 */
class MenuAccess
{
    private static $_MENU_ACCESS = [
        '/' => [ROLE_ID_NOT_LOGGED_IN, ROLE_ID_DEFAULT],
        '/signin' => [ROLE_ID_NOT_LOGGED_IN],
        '/lost_password' => [ROLE_ID_NOT_LOGGED_IN],
        '/register' => [ROLE_ID_NOT_LOGGED_IN],
    ];

    public static function GetForRole($RoleID)
    {
        $RoleID *= 1; // make sure it's an INT since we're doing strict comparisons

        $list = [];
        foreach (self::$_MENU_ACCESS as $url => $roles) {
            // use strict comparison since 0 is always true otherwise
            // https://stackoverflow.com/questions/13846769/php-in-array-0-value
            if (in_array($RoleID, $roles, true)) {
                $list[] = $url;
            }
        }
        return $list;
    }
}