<?php
$history = '<?php
if(!isset($User) || !$User->U_ID) exit_json([\'error\'=>\'Invalid Request\']));
		
/* @var $item ' . $c_name . ' */
$item = ' . $c_name . '::Get($Request->uuid);
if(!$item->VisibleTo($User))
	exit_json([\'error\'=>\'Invalid Request\']);

ob_start();
?>

<table class="table table-bordered" style="width: 800px;">
<thead>
	<tr>
		<th>Rev</th>
		<th>Value</th>
		<th>Was</th>
		<th>Now</th>
		<th>When</th>
		<th>By</th>
	</tr>
</thead>
<?php foreach($item->history as $cl) {  /* @var $cl ChangeLogClass */ if($item->IgnoreColumn($cl->column_name)) continue; ?>
<tr>
	<td><?php echo $cl->revision; ?></td>
	<td style="white-space: nowrap;"><?php echo $item->ColumnNameToNiceName($cl->column_name); ?></td>
	<td><?php echo $item->ValueToNiceValue($cl->column_name, $cl->current_value); ?></td>
	<td><?php echo $item->ValueToNiceValue($cl->column_name, $cl->new_value); ?></td>
	<td style="white-space: nowrap;"><?php echo StandardDateTime($cl->created_at); ?></td>
	<td style="white-space: nowrap;"><?php echo $cl->user->full_name; ?><br/>(<?php echo $cl->user->login_name; ?>)</td>
</tr>
<?php } ?>
</table>

<?php
$html = ob_get_clean();
$returnvals[\'html\'] = $html;

exit_json($returnvals);
';

$fp = fopen('json/_' . $c_name . '/history.json.php','w');
fwrite($fp,$history);
fclose($fp);

