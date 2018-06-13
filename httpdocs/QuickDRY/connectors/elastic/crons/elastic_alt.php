<?php
// php elastic_alt.php -hhost -uhttp://elastic:9200/index/type/ -felastic_schema/schema.json
$shortopts = '';
$shortopts .= 'h:';
$shortopts .= 'u:';
$shortopts .= 'f:';

$options = getopt($shortopts);

$_HOST = isset($options['h']) ? $options['h'] : '';
$_URL = isset($options['u']) ? $options['u'] : '';
$_FILE = isset($options['f']) ? $options['f'] : '';

if (!$_HOST || !$_URL || !$_FILE) {
    exit('USAGE: php ' . __FILE__ . ' -h<host> -u<url> -f<file> - json format>' . "\r\n");
}

require_once('../index.php');

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

Halt($res);



