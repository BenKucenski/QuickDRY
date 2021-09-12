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

  $file = __DIR__ . '/' . $class_map[$class];

  if (file_exists($file)) {
    require_once $file;
  }
}


spl_autoload_register('autoloader_QuickDRY_PHPMailer');


