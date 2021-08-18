<?php
// php elastic_delete_index.php -hhome -iindex -ttype

use QuickDRY\Connectors\Elastic_A;
use QuickDRY\Utilities\Debug;

$shortopts = 'h:';
$shortopts .= 'i:';
$shortopts .= 't:';

$options = getopt($shortopts);

$_HOST = $options['h'] ?? '';
$_INDEX = $options['i'] ?? '';
$_TYPE = $options['t'] ?? '';

if (!$_HOST || !$_INDEX) {
  exit('USAGE: php ' . __FILE__ . ' -h<host> -i<index> -t<type>' . "\r\n");
}

require_once __DIR__ . '/../index.php';

$res = 'null';
try {
  if ($_TYPE) {
    $res = Elastic_A::DeleteIndexType($_INDEX, $_TYPE);

  } else {
    $res = Elastic_A::DeleteIndex($_INDEX);
  }
} catch (Exception $e) {
  Debug::Halt($e->getMessage());
}

Debug::Halt($res);