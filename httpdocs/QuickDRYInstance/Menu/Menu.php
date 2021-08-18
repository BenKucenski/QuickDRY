<?php

namespace QuickDRYInstance\Menu;

use QuickDRY\Utilities\SafeClass;

class Menu extends SafeClass
{
    public static array $Menu = [
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

