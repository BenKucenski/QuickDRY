<?php
/* @var $Web Web */
/* @var $CurrentUser UserClass */

ob_start();

Metrics::Start('Controller');
if (file_exists($Web->ControllerFile)) {
    require_once $Web->ControllerFile;

    if (defined('PAGE_MODEL_STATIC')) { // static class
        $PageModel = PAGE_MODEL_STATIC;
        $PageModel::Construct($Request, $Session, $Cookie, $CurrentUser);
        $PageModel::DoInit();
        $_MASTERPAGE = $PageModel::$MasterPage ? $PageModel::$MasterPage : null;

        if ($Web->IsSecureMasterPage($_MASTERPAGE)) {
            if ($Web->AccessDenied) {
                if (!$CurrentUser || !$CurrentUser->id) {
                    HTTP::RedirectNotice('Please Sign In', '/signin');
                }
                CleanHalt([$CurrentUser->Roles, MenuAccess::GetPageRoles(CURRENT_PAGE)]);
            }
        }

        switch ($Server->REQUEST_METHOD) {
            case 'GET':
                $PageModel::DoGet();
                break;
            case 'POST':
                $PageModel::DoPost();
                break;
        }

        if ($Request->export) {
            switch (strtolower($Request->export)) {
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
            $PageModel = new $class($Request, $Session, $Cookie, $CurrentUser);
            $PageModel->Init();
            $_MASTERPAGE = $PageModel->MasterPage ? $PageModel->MasterPage : null;

            if ($Web->IsSecureMasterPage($_MASTERPAGE)) {
                if ($Web->AccessDenied) {
                    if (!$CurrentUser || !$CurrentUser->id) {
                        HTTP::RedirectNotice('Please Sign In', '/signin');
                    }
                    CleanHalt([$CurrentUser->Roles, MenuAccess::GetPageRoles(CURRENT_PAGE)]);
                }
            }

            switch ($Server->REQUEST_METHOD) {
                case 'GET':
                    $PageModel->Get();
                    break;
                case 'POST':
                    $PageModel->Post();
                    break;
            }

            if ($Request->export) {
                switch (strtolower($Request->export)) {
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
                if (!$CurrentUser || !$CurrentUser->id) {
                    HTTP::RedirectNotice('Please Sign In', '/signin');
                }
                CleanHalt([$CurrentUser->Roles, MenuAccess::GetPageRoles(CURRENT_PAGE)]);
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

if ($Session->pdf) {
    Metrics::Start('render pdf');
    switch ($Session->pdf_lib) {
        case 'webkit':
        default:
            require_once 'QuickDRY/pdf_output/webkit.php';
            break;
    }

    Metrics::Stop('render pdf');
    exit;
}