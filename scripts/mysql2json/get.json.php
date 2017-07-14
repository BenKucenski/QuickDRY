<?php
$get = '<?php
if(!isset($User) || !$User->U_ID) exit_json([\'error\'=>\'Invalid Request\']));
		
$returnvals = array();

if(isset($Request->uuid))
{
	/* @var $c ' . $c_name . ' */
	$c = ' . $c_name . '::Get([\'' . $primary[0] . '\'=>$Request->uuid]);
	if(!$c->VisibleTo($User))
		exit_json([\'error\'=>\'Invalid Request\']);

	$returnvals[\'serialized\'] = $c->ToArray();
	$returnvals[\'can_delete\'] = $c->CanDelete($User) ? 1 : 0;
}
else
	$returnvals[\'error\'] = \'' . CapsToSpaces(str_replace('Class','',$c_name)) . ' - Get: Bad params passed in!\';

exit_json($returnvals);
	';

$fp = fopen('json/_' . $c_name . '/get.json.php','w');
fwrite($fp,$get);
fclose($fp);