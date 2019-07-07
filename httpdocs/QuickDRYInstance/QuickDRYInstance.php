<?php
require_once 'Defines.php';


function autoloader_QuickDRY_Instance($class)
{
    $class_map = [
        'GenderClass' => 'FormElements/GenderClass.php',
        'MonthClass' => 'FormElements/MonthClass.php',
        'PerPageClass' => 'FormElements/PerPageClass.php',
        'RoleClass' => 'FormElements/RoleClass.php',
        'StatesClass' => 'FormElements/StatesClass.php',
        'YesNoClass' => 'FormElements/YesNoClass.php',

        'ChangeLogHandler' => 'Common/ChangeLogHandler.php',
        'FileClass' => 'Common/FileClass.php',
        'UserClass' => 'Common/UserClass.php',
        'UserManager' => 'Common/UserManager.php',

        'Menu' => 'Menu/Menu.php',
        'MenuAccess' => 'Menu/MenuAccess.php',
    ];

    if (!isset($class_map[$class])) {
        return;
    }

    $file = $class_map[$class];
    $file = 'QuickDRYInstance/' . $file;

    if (file_exists($file)) { // web
        require_once $file;
    } else {
        if (file_exists('../' . $file)) { // cron folder
            require_once '../' . $file;
        } else { // scripts folder
            require_once '../httpdocs/' . $file;
        }
    }
}


spl_autoload_register('autoloader_QuickDRY_Instance');