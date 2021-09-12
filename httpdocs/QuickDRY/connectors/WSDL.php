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

  $file = __DIR__ . '/' . $class_map[$class];

  if (file_exists($file)) {
    require_once $file;
  }
}

spl_autoload_register('autoloader_QuickDRY_WSDL');


