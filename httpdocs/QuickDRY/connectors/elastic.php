<?php

function autoloader_QuickDRY_Elastic($class)
{
    $class_map = [
        'TypeSubtype' => 'elastic/TypeSubtype.php',
        'Elastic_Base' => 'elastic/Elastic_Base.php',
        'Elastic_Core' => 'elastic/Elastic_Core.php',
        'Elastic_A' => 'elastic/Elastic_A.php',
        'Elastic_CodeGen' => 'elastic/Elastic_CodeGen.php',
    ];


    if (!isset($class_map[$class])) {
        return;
    }

    $file = $class_map[$class];
    $file = 'QuickDRY/connectors/' . $file;

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


spl_autoload_register('autoloader_QuickDRY_Elastic');

