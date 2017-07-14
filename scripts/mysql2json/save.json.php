<?php
$save = '<?php
if(!isset($User) || !$User->U_ID) exit_json([\'error\'=>\'Invalid Request\']);

$returnvals = [];

if($_SERVER[\'REQUEST_METHOD\'] == \'POST\')
{
	if(isset($_POST[\'serialized\']))
	{
		$req = PostFromSerialized($_POST[\'serialized\']);
		$primary = isset(' . $c_name . '::$_primary[0]) ? ' . $c_name . '::$_primary[0] : \'id\';
		if(isset($req[$primary]) && $req[$primary])
			$c = ' . $c_name . '::Get([$primary=>$req[$primary]]);
		else
			$c = new ' . $c_name . '();

		' . (in_array('practice_id', $column_names) ? '$req[\'practice_id\'] = isset($req[\'practice_id\']) && $req[\'practice_id\'] ? $req[\'practice_id\'] : $CurrentUser->practice_id;' : '') . '
		$c->FromRequest($req, false);
		if(!$c->VisibleTo($User))
			exit_json([\'error\'=>\'Invalid Request\']);
		$res = $c->FromRequest($req);

		if(!isset($res[\'error\']) || !$res[\'error\'])
		{
			$returnvals[\'success\'] = \'' . CapsToSpaces(str_replace('Class','',$c_name)) . ' Saved!\';
			$returnvals[$primary] = $c->$primary;
			$returnvals[\'serialized\'] = $c->ToArray();
		}
		else
			$returnvals[\'error\'] = $res[\'error\'];
	}
	else
		$returnvals[\'error\'] = \'' . CapsToSpaces(str_replace('Class','',$c_name)) . ' - Save: Bad params passed in!\';
}
else
	$returnvals[\'error\'] = \'' . CapsToSpaces(str_replace('Class','',$c_name)) . ' - Save: Bad Request sent!\';

exit_json($returnvals);
	';
$fp = fopen('json/_' . $c_name . '/save.json.php','w');
fwrite($fp,$save);
fclose($fp);