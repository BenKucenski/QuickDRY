<?php

use QuickDRY\Utilities\BarcodeClass;
use QuickDRY\Web\Request;

require_once '../QuickDRY/Web/Request.php';
require_once '../QuickDRY/utilities/BarcodeClass.php';

define('BASEDIR', str_replace('\\','/',dirname(__FILE__)).'/');

$Request = new Request();

$width = $Request->Get('w');
$height = $Request->Get('h');
$code = $Request->Get('c');

if(!$width) { // fix for IIS if Web.config doesn't work
    $matches = [];
    $pattern = '/(\d+)\/(\d+)\/(.*?)\.png/si';
    $query = $_SERVER['REQUEST_URI'];
    preg_match($pattern, $query, $matches);
    $width = $matches[1] ?? 0;
    $height = $matches[2] ?? 0;
    $code = $matches[3] ?? 0;
}

if(!$code) {
    exit('invalid');
}

if(file_exists(BASEDIR . $width . '/' . $height . '/' .$code . '.png'))
{
    header('Content-type: image/png');
    readfile(BASEDIR . $width . '/' . $height . '/' . $code . '.png');
}
else
{
    $img = BarcodeClass::Generate($width, $height, $code);
    header('Content-type: image/png');
    imagepng($img);
    imagedestroy($img);
}
exit();
