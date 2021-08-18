<?php
// php elastic_create_index.php -hhome -iindex -ttype


use QuickDRY\Connectors\Elastic_A;
use QuickDRY\Utilities\Debug;

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

require_once __DIR__ . '/../index.php';

$fp = fopen($_FILE,'r');
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
        }
        $res = Elastic_A::CreateIndexType($_INDEX, $_TYPE, $schema);
    }
} catch (Exception $e) {
  Debug::Halt($e->getMessage());
}

Debug::Halt($res);


