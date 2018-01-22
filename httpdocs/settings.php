<?php
define('SESSION_NAME','sessionname');
session_name(SESSION_NAME);
session_start();


ini_set ('track_errors', 1);
ini_set ('log_errors',   1);
ini_set ('error_log',    'logs/error.log');
ini_set('display_errors','On');

define('BASEDIR', str_replace('\\','/',dirname(__FILE__)).'/');

date_default_timezone_set('GMT');

define('META_TITLE','Site Name');