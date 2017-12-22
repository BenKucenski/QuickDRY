<?php

if (!$Session->page_orientation) {
    $Session->page_orientation = 'Portrait';
}

if (strcasecmp($Session->page_orientation, 'letter') == 0) {
    $Session->page_orientation = 'portrait';
}


if (!$Session->pdf_style) {
    $Session->pdf_style = 'default';
}

ob_start();
require_once 'style/' . $Session->pdf_style . '.php';
$_PAGE_HTML = ob_get_clean();


$_PAGE_HTML = preg_replace('/\.\.\//si', '/', $_PAGE_HTML);
$_PAGE_HTML = preg_replace('/\/+/si', '/', $_PAGE_HTML);
$_PAGE_HTML = str_replace('src="/', 'src="' . BASE_URL . '/', $_PAGE_HTML);
$_PAGE_HTML = str_replace('href="/', 'href="' . BASE_URL . '/', $_PAGE_HTML);
$_PAGE_HTML = str_replace('src=\'/', 'src=\'' . BASE_URL . '/', $_PAGE_HTML);
$_PAGE_HTML = str_replace('href=\'/', 'href=\'' . BASE_URL . '/', $_PAGE_HTML);

$hash = md5($_PAGE_HTML);

if (!is_dir(BASEDIR . 'temp/')) {
    mkdir(BASEDIR . 'temp/');
}

$html_file = BASEDIR . 'temp/' . $hash . '.html';

$fp = fopen($html_file, 'w');
fwrite($fp, $_PAGE_HTML);
fclose($fp);


$Description = $Session->name;
$FileName = $html_file . '.pdf';


$cmd = BASEDIR . 'QuickDRY\\bin\\wkhtmltopdf.exe --javascript-delay 5000 --enable-javascript --disable-smart-shrinking -O ' . $Session->page_orientation . ' ' . $html_file . ' ' . $FileName;

$output = [];
exec($cmd, $output);
$output = implode(PHP_EOL, $output);


$e = error_get_last();
if (!is_null($e) && !stristr($e['message'], 'statically')) {
    exit('<p><b>There has been an error processing this page.</b></p>' . Debug($e, false, true));
}

$pdf_name = $Session->name;

unset($Session->pdf);
unset($Session->page_orientation);
unset($Session->name);


if (!file_exists($FileName)) {
    Debug::Halt(['file not created', 'file' => $FileName, 'cmd' => $cmd, 'output' => $output]);
}

if ($Session->post_pdf_redirect) {
    header('location: ' . $Session->post_pdf_redirect);
    unset($Session->post_pdf_redirect);
    exit();
}


header('Content-type: application/pdf');
header('Content-Disposition: inline; filename="' . $pdf_name . '"');
header('Expires: 0');
header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
header('Pragma: public');
readfile($FileName);

