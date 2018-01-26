<?php
/* @var $Web Web */
/* @var $CurrentUser UserClass */

ob_start();

Metrics::Start('Controller');
if (file_exists($Web->ControllerFile)) {
    require_once $Web->ControllerFile;

    if (defined('PAGE_MODEL_STATIC')) { // static class
        $PageModel = PAGE_MODEL_STATIC;
        $PageModel::Construct($Web->Request, $Web->Session, $Web->Cookie, $Web->CurrentUser);
        $PageModel::DoInit();
        $Web->MasterPage = $PageModel::$MasterPage ? $PageModel::$MasterPage : null;

        if ($Web->IsSecureMasterPage()) {
            if ($Web->AccessDenied) {
                if (!$Web->CurrentUser || !$Web->CurrentUser->id) {
                    HTTP::RedirectNotice('Please Sign In', '/signin');
                } else {
                    HTTP::RedirectError('Invalid Page');
                }
            }
        }

        switch ($Web->Server->REQUEST_METHOD) {
            case 'GET':
                $PageModel::DoGet();
                break;
            case 'POST':
                $PageModel::DoPost();
                break;
        }

        if ($Web->Request->export) {
            switch (strtolower($Web->Request->export)) {
                case 'xls':
                    if (method_exists($PageModel, 'DoExportToXLS')) {
                        $PageModel::DoExportToXLS();
                    } else {
                        exit('DoExportToXLS Not Implemented: ' . $PageModel);
                    }
                    exit;
                case 'json':
                    if (method_exists($PageModel, 'DoExportToJSON')) {
                        $PageModel::DoExportToJSON();
                    } else {
                        exit('DoExportToJSON Not Implemented: ' . $PageModel);
                    }
                    exit;
                case 'pdf':
                    if (method_exists($PageModel, 'DoExportToPDF')) {
                        $PageModel::DoExportToPDF();
                        $Web->RenderPDF = true;
                        $Web->PDFPageOrientation = $PageModel::$PDFPageOrientation;
                        $Web->PDFFileName = $PageModel::$PDFFileName;
                        $Web->PDFPostRedirect = $PageModel::$PDFPostRedirect;
                        $Web->MasterPage = $PageModel::$MasterPage ? $PageModel::$MasterPage : null;
                    } else {
                        exit('DoExportToPDF Not Implemented');
                    }
                    break;
            }
        }
    } else {
        if (defined('PAGE_MODEL')) { // instance class
            $class = PAGE_MODEL;
            /* @var $PageModel BasePage */
            $PageModel = new $class($Web->Request, $Web->Session, $Web->Cookie, $Web->CurrentUser);
            $PageModel->Init();
            $Web->MasterPage = $PageModel->MasterPage ? $PageModel->MasterPage : null;

            if ($Web->IsSecureMasterPage()) {
                if ($Web->AccessDenied) {
                    if (!$Web->CurrentUser || !$Web->CurrentUser->id) {
                        HTTP::RedirectNotice('Please Sign In', '/signin');
                    } else {
                        HTTP::RedirectError('Invalid Page');
                    }
                }
            }

            switch ($Web->Server->REQUEST_METHOD) {
                case 'GET':
                    $PageModel->Get();
                    break;
                case 'POST':
                    $PageModel->Post();
                    break;
            }

            if ($Web->Request->export) {
                switch (strtolower($Web->Request->export)) {
                    case 'xls':
                        if (method_exists($PageModel, 'ExportToXLS')) {
                            $PageModel->ExportToXLS();
                        } else {
                            exit('ExportToXLS Not Implemented: ' . get_class($PageModel));
                        }
                        exit;
                    case 'json':
                        if (method_exists($PageModel, 'ExportToJSON')) {
                            $PageModel->ExportToJSON();
                        } else {
                            exit('ExportToJSON Not Implemented');
                        }
                        exit;
                    case 'pdf':
                        if (method_exists($PageModel, 'ExportToPDF')) {
                            $PageModel->ExportToPDF();
                            $Web->RenderPDF = true;
                            $Web->PDFPageOrientation = $PageModel->PDFPageOrientation;
                            $Web->PDFFileName = $PageModel->PDFFileName;
                            $Web->PDFPostRedirect = $PageModel->PDFPostRedirect;
                            $Web->MasterPage = $PageModel::$MasterPage ? $PageModel::$MasterPage : null;
                        } else {
                            exit('ExportToPDF Not Implemented');
                        }
                        break;
                }
            }

        } else { // no page model

            if ($Web->AccessDenied) {
                if (!$Web->CurrentUser || !$Web->CurrentUser->id) {
                    HTTP::RedirectNotice('Please Sign In', '/signin');
                } else {
                    HTTP::RedirectError('Invalid Page');
                }
            }
        }
    }
}

Metrics::Stop('Controller');

Metrics::Start('View');
if (file_exists($Web->ViewFile)) {
    require_once $Web->ViewFile;
}
Metrics::Stop('View');

$Web->HTML = ob_get_clean();


if ($Web->RenderPDF) {

    ob_start();
    if (file_exists('masterpages/' . $Web->MasterPage . '.php')) {
        require_once 'masterpages/' . $Web->MasterPage . '.php';
    } else {
        Debug::Halt($Web->MasterPage . ' does not exist');
    }
    $Web->HTML = ob_get_clean();


    Metrics::Start('render pdf');
    require_once 'QuickDRY/pdf_output/webkit.php';

    Metrics::Stop('render pdf');
    exit;
}

if (file_exists('masterpages/' . $Web->MasterPage . '.php')) {
    require_once 'masterpages/' . $Web->MasterPage . '.php';
} else {
    Debug::Halt($Web->MasterPage . ' masterpage does not exist');
}