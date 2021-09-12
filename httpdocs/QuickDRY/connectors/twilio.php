<?php
// composer require twilio/sdk
// https://support.twilio.com/hc/en-us/articles/235279367-Twilio-PHP-helper-library-SSL-certificate-problem-on-Windows


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

  $file = __DIR__ . '/' . $class_map[$class];

  if (file_exists($file)) {
    require_once $file;
  }
}

spl_autoload_register('autoloader_QuickDRY_Twilio');




