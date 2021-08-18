<?php

function autoloader_QuickDRY_PHPMailer($class)
{
    $class_map = [
        'SMTP' => 'phpmailer/SMTP.php',
        'POP3' => 'phpmailer/POP3.php',
        'PHPMailer' => 'phpmailer/PHPMailer.php',
        'phpmailerException' => 'phpmailer/phpmailerException.php',
    ];

    if (!isset($class_map[$class])) {
        return;
    }

    $file = $class_map[$class];
    $file = 'QuickDRY/utilities/' . $file;

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


spl_autoload_register('autoloader_QuickDRY_PHPMailer');


