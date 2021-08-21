<?php

use QuickDRY\Connectors\Elastic_A;
use QuickDRY\Utilities\Debug;
use QuickDRY\Utilities\SafeClass;

$shortopts = 'h:';
$shortopts .= 'i:';
$shortopts .= 't:';
$shortopts .= 'f:';

$options = getopt($shortopts);

$_HOST = $options['h'] ?? '';
$_INDEX = $options['i'] ?? '';
$_TYPE = $options['t'] ?? '';
$_FILE = $options['f'] ?? '';

if (!$_HOST || !$_INDEX || !$_FILE) {
  exit('USAGE: php ' . __FILE__ . ' -h<host> -i<index> -t<type> -f<file> - json format>' . "\r\n");
}

require_once('../index.php');

class CreateIndex extends SafeClass
{
  public function __construct($_INDEX, $_TYPE, $_FILE)
  {
    $fp = fopen($_FILE, 'r');
    $schema = fread($fp, filesize($_FILE));
    fclose($fp);

    $schema = json_decode($schema, true);

    $res = 'null';
    try {
      if (!$_TYPE) {
        $res = Elastic_A::CreateIndex($_INDEX, $schema);
      } else {
        // make sure main index exists
        try {
          $res = Elastic_A::CreateIndex($_INDEX, null);
        } catch (Exception $e) {
          // ignore this, we don't care, it just needs to exist
          print_r($res);
        }
        $res = Elastic_A::CreateIndexType($_INDEX, $_TYPE, $schema);
      }
    } catch (Exception $e) {
      Debug::CleanHalt($e->getMessage());
    }

    Debug::CleanHalt($res);
  }
}

$cron = new CreateIndex($_INDEX, $_TYPE, $_FILE);
