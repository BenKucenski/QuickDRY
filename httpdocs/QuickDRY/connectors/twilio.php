<?php
function autoloader_QuickDRY_Twilio($class)
{
    $class_map = [
        'TwilioDNC' => 'twilio/TwilioDNC.php',
        'TwilioLog' => 'twilio/TwilioLog.php',
        'Twilio' => 'twilio/Twilio.php',
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

spl_autoload_register('autoloader_QuickDRY_Twilio');


require 'twilio/vendor/Twilio.php';



