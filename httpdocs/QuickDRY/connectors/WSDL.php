<?php

function autoloader_QuickDRY_WSDL($class)
{
    $class_map = [
        'WSDLParameter' => 'WSDL/WSDLParameter.php',
        'WSDLResponse' => 'WSDL/WSDLResponse.php',
        'WSDLResult' => 'WSDL/WSDLResult.php',
        'WSDLFunction' => 'WSDL/WSDLFunction.php',
        'WSDL2Code' => 'WSDL/WSDL2Code.php',
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

spl_autoload_register('autoloader_QuickDRY_WSDL');


