<?php
$opts = 'h:'; // host
$opts .= 'c:'; // connection class name
$opts .= 'p:'; // prefix
$opts .= 'e::'; // exclusions

$options = getopt($opts);

$_HOST = isset($options['h']) ? $options['h'] : '';
$_CONNECTION = isset($options['c']) ? $options['c'] : '';
$_PREFIX = isset($options['p']) ? $options['p'] : '';
$_EXCLUSIONS = isset($options['e']) ? explode(',', $options['e']) : [];

if(!$_HOST || !$_CONNECTION || !$_PREFIX) {
    exit('-h<host> required -c<connection class name> required -p<prefix> required');
}

require_once '../httpdocs/localsettings.php';
require_once '../httpdocs/init.php';

Log::Insert($options, true);

$CodeGen = new Elastic_CodeGen($_CONNECTION, $_PREFIX, $_EXCLUSIONS);