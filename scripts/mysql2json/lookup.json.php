<?php

$lookup = '<?php
if(!isset($User) || !$User->U_ID) exit_json([\'error\'=>\'Invalid Request\']));
		
$returnvals = array();

$search = strtoupper($_GET[\'term\']);
if(strlen($search) < 1) {
	$returnvals[] = array(\'id\' => 0, \'value\' => \'No Results Found\');
	exit_json($returnvals);
}

$returnvals = ' . $c_name .  '::Suggest($search);
exit_json($returnvals);
	';

$fp = fopen('json/_' . $c_name . '/json.lookup.php','w');
fwrite($fp,$lookup);
fclose($fp);