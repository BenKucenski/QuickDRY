<?php
require_once 'Defines.php';


function autoloader_QuickDRY_Instance($class)
{
  $class_map = [
    'QuickDRYInstance\FormElements\GenderClass' => 'FormElements/GenderClass.php',
    'QuickDRYInstance\FormElements\MonthClass' => 'FormElements/MonthClass.php',
    'QuickDRYInstance\FormElements\PerPageClass' => 'FormElements/PerPageClass.php',
    'QuickDRYInstance\FormElements\RoleClass' => 'FormElements/RoleClass.php',
    'QuickDRYInstance\FormElements\StatesClass' => 'FormElements/StatesClass.php',
    'QuickDRYInstance\FormElements\YesNoClass' => 'FormElements/YesNoClass.php',

    'QuickDRYInstance\Common\ChangeLog' => 'Common/ChangeLog.php',
    'QuickDRYInstance\Common\ChangeLogHistory' => 'Common/ChangeLogHistory.php',
    'QuickDRYInstance\Common\FileClass' => 'Common/FileClass.php',
    'QuickDRYInstance\Common\CronLog' => 'Common/CronLog.php',
    'QuickDRYInstance\Common\UserClass' => 'Common/UserClass.php',
    'QuickDRYInstance\Common\UserManager' => 'Common/UserManager.php',

    'QuickDRYInstance\Menu\Menu' => 'Menu/Menu.php',
    'QuickDRYInstance\Menu\MenuAccess' => 'Menu/MenuAccess.php',
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