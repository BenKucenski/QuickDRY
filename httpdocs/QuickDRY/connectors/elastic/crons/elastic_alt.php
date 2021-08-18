<?php
// php elastic_alt.php -hhost -uhttp://elastic:9200/index/type/ -felastic_schema/schema.json
use QuickDRY\Connectors\Curl;
use QuickDRY\Utilities\Debug;

$shortopts = 'h:';
$shortopts .= 'u:';
$shortopts .= 'f:';

$options = getopt($shortopts);

$_HOST = $options['h'] ?? '';
$_URL = $options['u'] ?? '';
$_FILE = $options['f'] ?? '';

if (!$_HOST || !$_URL || !$_FILE) {
    exit('USAGE: php ' . __FILE__ . ' -h<host> -u<url> -f<file> - json format>' . "\r\n");
}

require_once __DIR__ . '/../index.php';

$fp = fopen($_FILE,'r');
if(!$fp) {
    exit($_FILE . ' cannot be read');
}

$schema = fread($fp, filesize($_FILE));
fclose($fp);

if(!$schema) {
    exit('empty schema file');
}
echo print_r($schema, true) . PHP_EOL;

$res = Curl::Post($_URL,$schema);

Debug::Halt($res);



