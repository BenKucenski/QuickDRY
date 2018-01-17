<?php
/* @var $Web Web */
if (!$Web->Session->page_orientation) {
    $Web->Session->page_orientation = 'Portrait';
}

if (strcasecmp($Web->Session->page_orientation, 'letter') == 0) {
    $Web->Session->page_orientation = 'portrait';
}


if (!$Web->Session->pdf_style) {
    $Web->Session->pdf_style = 'default';
}

ob_start();
require_once 'style/' . $Web->Session->pdf_style . '.php';
$_CSS_HTML = ob_get_clean();

$_PAGE_HTML = $_CSS_HTML . $_PAGE_HTML;

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


$Description = $Web->Session->name;
$FileName = $html_file . '.pdf';


$cmd = BASEDIR . 'QuickDRY\\bin\\wkhtmltopdf.exe --javascript-delay 5000 --enable-javascript --disable-smart-shrinking -O ' . $Web->Session->page_orientation . ' ' . $html_file . ' ' . $FileName;

$output = [];
exec($cmd, $output);
$output = implode(PHP_EOL, $output);


$e = error_get_last();
if (!is_null($e) && !stristr($e['message'], 'statically')) {
    exit('<p><b>There has been an error processing this page.</b></p>' . Debug($e, false, true));
}

$pdf_name = $Web->Session->name;

unset($Web->Session->pdf);
unset($Web->Session->page_orientation);
unset($Web->Session->name);


if (!file_exists($FileName)) {
    Debug::Halt(['file not created', 'file' => $FileName, 'cmd' => $cmd, 'output' => $output]);
}

if ($Web->Session->post_pdf_redirect) {
    header('location: ' . $Web->Session->post_pdf_redirect);
    unset($Web->Session->post_pdf_redirect);
    exit();
}


header('Content-type: application/pdf');
header('Content-Disposition: inline; filename="' . $pdf_name . '"');
header('Expires: 0');
header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
header('Pragma: public');
readfile($FileName);

