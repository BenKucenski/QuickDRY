<?php
$opts = 'h::';
$opts .= 'd::';
$opts .= 'c::';
$opts .= 'u::';
$opts .= 'v::';
$opts .= 'i::';
$opts .= 'm::';
$opts .= 'l::';
$opts .= 'f::';

$options = getopt($opts);

$_HOST = isset($options['h']) ? $options['h'] : '';
$_DATABASE = isset($options['d']) ? $options['d'] : '';
$_DATABASE_CONSTANT = isset($options['c']) ? $options['c'] : '';
$_USER_CLASS = isset($options['u']) ? $options['u'] : '';
$_USER_VAR = isset($options['v']) ? $options['v'] : '';
$_USER_ID_COLUMN = isset($options['i']) ? $options['i'] : '';
$_MASTERPAGE = isset($options['m']) ? $options['m'] : '';
$_LOWERCASE_TABLE = isset($options['l']) ? $options['l'] : '';
$_USE_FK_COLUMN_NAME = isset($options['f']) ? $options['f'] : '';

if(!$_HOST || !$_DATABASE) {
    exit(basename(__FILE__) . ' usage: -h<host> -d<database> -c<database constant optional> -u<user class> -v<user variable> -i<user id column>' . PHP_EOL);
}

$_DATABASE_PREFIX = $_DATABASE_CONSTANT ? $_DATABASE_CONSTANT : $_DATABASE;

include '../httpdocs/localsettings.php';
include '../httpdocs/init.php';


$CodeGen = new MSSQL_CodeGen();
$CodeGen->Init($_DATABASE, $_DATABASE_CONSTANT, $_USER_CLASS, $_USER_VAR, $_USER_ID_COLUMN, $_MASTERPAGE, $_LOWERCASE_TABLE, $_USE_FK_COLUMN_NAME);
$CodeGen->GenerateClasses();
$CodeGen->GenerateJSON();
