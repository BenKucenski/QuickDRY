<?php

$_HOST = isset($argv[2]) ? $argv[2] : null;

include '../httpdocs/localsettings.php';
include '../httpdocs/init.php';

MySQL_Connection::$use_log = false; // must turn off logging or it screws things up since we're not working with classes

$database = isset($argv[1]) ? $argv[1] : DB_BASE;

MySQL_A::SetDatabase($database);

if(!is_dir('json'))
	mkdir('json');
if(!is_dir('manage'))
	mkdir('manage');

$sql = 'show full tables where Table_Type != \'VIEW\'';
$tables = MySQL_A::query($sql);
$modules = array();

foreach($tables['data'] as $table)
{
	$table_name = $table['Tables_in_' . $database];

	echo "$table_name\r\n";

	$sql = '
		SHOW COLUMNS FROM
			`' . $database . '`.`' . $table_name . '`
	';
	$columns = MySQL_A::query($sql);
	$cols = array();
	GenerateJSON($table_name, $columns['data']) . '\';';
}

$fp = fopen('nav.txt','a');
fwrite($fp, '"' . $database . '" => array("links"=>array(' . "\r\n");
foreach($tables['data'] as $table)
{
	$table_name = $table['Tables_in_' . $database];
	$c_name = SQL_Base::TableToClass($database, $table_name);

	fwrite($fp,"'" . $c_name . "' => '/manage/" . $c_name . "',\r\n");
}
fwrite($fp, ')),' . "\r\n");
fclose($fp);

function GenerateJSON($table_name, $cols)
{
	global $database;

	$column_names = array();
	foreach($cols as $col)
		$column_names[] = $col['Field'];

	$c_name = SQL_Base::TableToClass($database, $table_name);
	$dlg_name = 'dlg_' . $c_name;

	$sql = '
		SHOW INDEXES FROM
			`' . $table_name . '`
	';
	$res = MySQL_A::query($sql);
	$unique = [];
	$primary = [];
	if(!isset($res['data'][0])) {
		// don't care
	}

	foreach($res['data'] as $row)
	{
		if($row['Non_unique'] == 0 && strcmp($row['Key_name'],'PRIMARY') != 0)
			$unique[] = $row['Column_name'];
		if(strcmp($row['Key_name'],'PRIMARY') == 0)
			$primary[] = $row['Column_name'];
	}

	if(!isset($primary[0])) {
		// don't care
	}

	$page_dir = str_replace('Class','',$c_name);

	if(!is_dir('json/_' . $c_name))
	{
		mkdir('json/_' . $c_name);
		mkdir('json/_' . $c_name . '/controls');
	}

	if(!is_dir('manage/' . $page_dir))
		mkdir('manage/' . $page_dir);

	include 'mysql2json/save.json.php';
	include 'mysql2json/get.json.php';
	include 'mysql2json/lookup.json.php';
	include 'mysql2json/delete.json.php';
	include 'mysql2json/history.json.php';
	include 'mysql2json/add.php';
	include 'mysql2json/history.php';
	include 'mysql2json/manage.php';
}