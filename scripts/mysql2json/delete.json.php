<?php
$unique_php = '';
$u_req = array();
$u_sql = array();
$u_ret = array();
if(sizeof($unique))
{
	foreach($unique as $u)
	{
		$u_req[] = '$Request->' . $u;
		$u_seq[] = "'$u'=>\$Request->$u";
		$u_ret[] = "\$returnvals['$u'] = \$Request->$u;";
	}

	$unique_php = '
if(' . implode(' && ', $u_req) . ')
{
	$t = ' . $c_name . '::Get(array(' . implode(', ',$u_seq) . '));
	$Request->uuid = $t->' . ($primary[0] ? $primary[0] : $unique[0]) . ';
}
';
}


$delete = '<?php
if(!isset($User) || !$User->U_ID) exit_json([\'error\'=>\'Invalid Request\']));
		
$returnvals = array();

' . $unique_php . '

if($Request->uuid && is_numeric($Request->uuid))
{
	/* @var $c ' . $c_name . ' */
	$c = ' . $c_name . '::Get(["' . $primary[0] . '"=>$Request->uuid]);
	if(is_null($c) || !$c->VisibleTo($User) || !$c->CanDelete($User))
		exit_json([\'error\'=>\'Invalid Request\']);

	$res = $c->Remove($User);
	if(!isset($res[\'error\']) || !$res[\'error\'])
	{
		$returnvals[\'success\'] = \'' . CapsToSpaces(str_replace('Class','',$c_name)) . ' Removed\';
		$returnvals[\'uuid\'] = $Request->uuid;
		' . implode("\r\n\t\t",$u_ret) . '
	}
	else
		$returnvals[\'error\'] = $res[\'error\'];
}
else
	$returnvals[\'error\'] = \'' . CapsToSpaces(str_replace('Class','',$c_name)) . ' - Get: Bad params passed in!\';

exit_json($returnvals);
';

$fp = fopen('json/_' . $c_name . '/delete.json.php','w');
fwrite($fp,$delete);
fclose($fp);