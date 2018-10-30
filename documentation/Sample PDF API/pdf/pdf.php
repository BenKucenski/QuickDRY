<?php
if(!$Web->Request->page_orientation) {
    $Web->Request->page_orientation = 'Portrait';
}

if(strcasecmp($Web->Request->page_orientation,'letter') == 0) {
    $Web->Request->page_orientation = 'portrait';
}

$Web->Request->name = $Web->Request->name ? $Web->Request->name : 'output.pdf';

$hash = md5($Web->HTML);

if (!$Web->PDFPageOrientation) {
    $Web->PDFPageOrientation = $Web->Request->page_orientation;
}

if (!is_dir(DOC_ROOT_PATH . '/temp/')) {
    mkdir(DOC_ROOT_PATH . '/temp/');
}

$html_file = DOC_ROOT_PATH . '/temp/' . $hash . '.html';

$fp = fopen($html_file, 'w');
fwrite($fp, $Web->HTML);
fclose($fp);


$file = new FileClass();
$file->file_name = $Web->Request->name;
$file->file_hash = $hash;
$file->file_size = strlen($Web->HTML);
$file->file_ext = 'pdf';
$file->file_type = 'application/pdf';
$file->created_at = Dates::Timestamp();
$file->user_id = null;

$footer = '';
$params = [];
$params[] = '--javascript-delay 5000';
$params[] = '--enable-javascript';
$params[] = '--disable-smart-shrinking';
$params[] = '--page-size ' . ($Web->PDFPageSize ? $Web->PDFPageSize : PDF_PAGE_SIZE_LETTER);

// $params[] = '--debug-javascript';
$params[] = '-O ' . $Web->PDFPageOrientation;
if($Web->PDFHeader) {
    $params[] = '--header-html "' . $Web->PDFHeader . '"';
}
if($Web->PDFFooter) {
    $params[] ='--footer-html "' . $Web->PDFFooter . '"';
}

$FileName = $html_file . '.pdf';

$cmd = DOC_ROOT_PATH . '\\QuickDRY\\bin\\wkhtmltopdf.exe ' . implode(' ', $params) . ' ' . $html_file . ' ' . $FileName;
Log::Insert($cmd);

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

unset($Web->Request->pdf);
unset($Web->Request->pagetype);
unset($Web->Request->page_orientation);
unset($Web->Request->pdf_lib);
unset($Web->Request->entity_type_id);
unset($Web->Request->entity_id);
unset($Web->Request->pdf_footer);
unset($Web->Request->pdf_style);

$e = error_get_last();
if(!is_null($e) && !stristr($e['message'],'statically'))
{
    exit('<p><b>There has been an error processing this page.</b></p>');
}

if($Web->Request->post_pdf_redirect)
{
    header('location: ' . $Web->Request->post_pdf_redirect);
    unset($Web->Request->post_pdf_redirect);
    exit();
}

header('Content-type: application/pdf');
header('Content-Disposition: inline; filename="' . $Web->Request->name . '"');
header('Expires: 0');
header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
header('Pragma: public');
readfile($FileName);
exit;

