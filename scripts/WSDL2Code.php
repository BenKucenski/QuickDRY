<?php
$opts = 'h:';
$opts .= 'u:';
$opts .= 'c:';
$opts .= 'b::';

$options = getopt($opts);

$_HOST = isset($options['h']) ? $options['h'] : '';
$_URL = isset($options['u']) ? $options['u'] : '';
$_CLASS = isset($options['c']) ? $options['c'] : '';
$_BASE = isset($options['b']) ? $options['b'] : '../httpdocs';

if (!$_HOST || !$_URL) {
    exit('-h<host> -u<WSDL url> -c<class name> required');
}

include '../httpdocs/index.php';


$cron = new WSDL2Code();
$cron->Generate($_URL, $_CLASS, $_BASE);


