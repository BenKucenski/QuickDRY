<?php
// php elastic_delete_index.php -hhome -iindex -ttype

$shortopts = '';
$shortopts .= 'h:';
$shortopts .= 'i:';
$shortopts .= 't:';

$options = getopt($shortopts);

$_HOST = isset($options['h']) ? $options['h'] : '';
$_INDEX = isset($options['i']) ? $options['i'] : '';
$_TYPE = isset($options['t']) ? $options['t'] : '';

if (!$_HOST || !$_INDEX) {
    exit('USAGE: php ' . __FILE__ . ' -h<host> -i<index> -t<type>' . "\r\n");
}

require_once('../index.php');

$res = 'null';
try {
    if ($_TYPE) {
        $res = Elastic_A::DeleteIndexType($_INDEX, $_TYPE);

    } else {
        $res = Elastic_A::DeleteIndex($_INDEX);
    }
} catch (Exception $e) {
    CleanHalt($e->getMessage());
}

CleanHalt($res);