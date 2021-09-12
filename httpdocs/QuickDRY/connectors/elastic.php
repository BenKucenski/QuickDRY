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

  $file = __DIR__ . '/' . $class_map[$class];

  if (file_exists($file)) {
    require_once $file;
  }
}


spl_autoload_register('autoloader_QuickDRY_Elastic');

