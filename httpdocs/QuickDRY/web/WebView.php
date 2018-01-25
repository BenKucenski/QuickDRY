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
                        exit('ExportToXLS Not Implemented: ' . $PageModel);
                    }
                    exit;
                case 'json':
                    if (method_exists($PageModel, 'DoExportToJSON')) {
                        $PageModel::DoExportToJSON();
                    } else {
                        exit('ToJSON Not Implemented: ' . $PageModel);
                    }
                    exit;
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
                            exit('ToJSON Not Implemented');
                        }
                        exit;
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

$_PAGE_HTML = ob_get_clean();

if ($Web->Session->pdf) {

    ob_start();
    if(file_exists('masterpages/' . $Web->MasterPage . '.php')) {
        require_once 'masterpages/' . $Web->MasterPage . '.php';
    } else {
        Debug::Halt($Web->MasterPage . ' does not exist');
    }
    $_PAGE_HTML = ob_get_clean();

    Metrics::Start('render pdf');
    switch ($Web->Session->pdf_lib) {
        case 'webkit':
        default:
            require_once 'QuickDRY/pdf_output/webkit.php';
            break;
    }

    Metrics::Stop('render pdf');
    exit;
}

if(file_exists('masterpages/' . $Web->MasterPage . '.php')) {
    require_once 'masterpages/' . $Web->MasterPage . '.php';
} else {
    Debug::Halt($Web->MasterPage . ' does not exist');
}