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
$FileName = $html_file . '.pdf';

if(defined('PDF_API')) {
    $res = Curl::Post(PDF_API, 'html=' . urlencode($Web->HTML));
    $fp = fopen($FileName, 'w');
    fwrite($fp, $res->Body);
    fclose($fp);
} else {

    $fp = fopen($html_file, 'w');
    fwrite($fp, $Web->HTML);
    fclose($fp);

    $params = [];
    $params[] = '--javascript-delay 5000';
    $params[] = '--enable-javascript';
    $params[] = '--disable-smart-shrinking';
    $params[] = '--page-size ' . ($Web->PDFPageSize ? $Web->PDFPageSize : PDF_PAGE_SIZE_LETTER);

// $params[] = '--debug-javascript';
    $params[] = '-O ' . $Web->PDFPageOrientation;
    if($Web->PDFSimplePageNumbers) {
        $params[] = '--footer-center [page]/[topage]';
    } else {
        if ($Web->PDFHeader) {
            $params[] = '--header-html "' . $Web->PDFHeader . '"';
        }
        if ($Web->PDFFooter) {
            $params[] = '--footer-html "' . $Web->PDFFooter . '"';
        }
    }


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

    if ($Web->PDFPostRedirect) {
        header('location: ' . $Web->PDFPostRedirect);
        unset($Web->PDFPostRedirect);
        exit();
    }
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
