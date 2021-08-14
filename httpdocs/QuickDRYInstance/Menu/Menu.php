<?php
namespace QuickDRYInstance;

use QuickDRY\Utilities\SafeClass;

/**
 * Class Menu
 */
class Menu extends SafeClass
{
    public static $Menu = [
        'Sign In' => [
            'sort_order' => '0',
            'link' => '/signin',
        ],

        'Home' => [

            'sort_order' => '-10',
            'link' => '/main',
        ],
    ];
}

