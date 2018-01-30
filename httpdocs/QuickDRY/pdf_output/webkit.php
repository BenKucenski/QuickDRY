<?php

/* @var $Web Web */
if (!$Web->PDFPageOrientation) {
    $Web->PDFPageOrientation = 'Portrait';
}

if (strcasecmp($Web->PDFPageOrientation, 'letter') == 0) {
    $Web->PDFPageOrientation = 'portrait';
}

$Web->HTML = str_replace('src="/', 'src="' . BASE_URL . '/', $Web->HTML);
$Web->HTML = str_replace('href="/', 'href="' . BASE_URL . '/', $Web->HTML);
$Web->HTML = str_replace('src=\'/', 'src=\'' . BASE_URL . '/', $Web->HTML);
$Web->HTML = str_replace('href=\'/', 'href=\'' . BASE_URL . '/', $Web->HTML);

$hash = md5($Web->HTML);

if (!is_dir(DOC_ROOT_PATH . '/temp/')) {
    mkdir(DOC_ROOT_PATH . '/temp/');
}

$html_file = DOC_ROOT_PATH . '/temp/' . $hash . '.html';

$fp = fopen($html_file, 'w');
fwrite($fp, $Web->HTML);
fclose($fp);


$FileName = $html_file . '.pdf';


$cmd = DOC_ROOT_PATH . '\\QuickDRY\\bin\\wkhtmltopdf.exe --javascript-delay 5000 --enable-javascript --disable-smart-shrinking -O ' . $Web->PDFPageOrientation . ' ' . $html_file . ' ' . $FileName;

$output = [];
exec($cmd, $output);
$output = implode(PHP_EOL, $output);


$e = error_get_last();
if (!is_null($e) && !stristr($e['message'], 'statically')) {
    Debug::Halt($e);
}


if (!file_exists($FileName)) {
    Debug::Halt(['file not created', 'file' => $FileName, 'cmd' => $cmd, 'output' => $output]);
}

if ($Web->PDFPostRedirect) {
    header('location: ' . $Web->PDFPostRedirect);
    unset($Web->PDFPostRedirect);
    exit();
}

if(isset($_SERVER['HTTP_HOST'])) {
    header('Content-type: application/pdf');
    header('Content-Disposition: inline; filename="' . $Web->PDFFileName . '"');
    header('Expires: 0');
    header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
    header('Pragma: public');
    readfile($FileName);
} else {
    rename($FileName, $Web->PDFFileName);
}
