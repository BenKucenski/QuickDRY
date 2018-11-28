<?php
/* @var $Web Web */
/* @var $CurrentUser UserClass */

if(defined('UNDER_MAINTENANCE') && UNDER_MAINTENANCE) {
    $Web->PageMode = QUICKDRY_MODE_BASIC;
    $Web->ControllerFile = null;
    $Web->MasterPage = MASTERPAGE_BLANK;
    $Web->ViewFile = 'QuickDRYInstance/UnderMaintenance.php';
}

ob_start();

Metrics::Start('Controller');
if (file_exists($Web->ControllerFile)) {
    require_once $Web->ControllerFile;

    $PageMode = QUICKDRY_MODE_BASIC;

    if ($Web->PageMode === QUICKDRY_MODE_STATIC || $Web->StaticModel || defined('PAGE_MODEL_STATIC')) { // static class
        $PageModel = $Web->StaticModel ? $Web->StaticModel : ($Web->PageClass ? $Web->PageClass : PAGE_MODEL_STATIC);

        if (is_numeric($PageModel[0])) {
            $PageModel = 'i' . $PageModel;
        }
        if (class_exists($PageModel)) {
            $PageMode = QUICKDRY_MODE_STATIC;
        }
    }

    if ($Web->PageMode === QUICKDRY_MODE_INSTANCE || $Web->InstanceModel || defined('PAGE_MODEL')) { // instance class
        $class = $Web->InstanceModel ? $Web->InstanceModel : ($Web->PageClass ? $Web->PageClass : PAGE_MODEL);

        if (is_numeric($class[0])) {
            $class = 'i' . $class;
        }
        if (class_exists($class)) {
            $PageMode = QUICKDRY_MODE_INSTANCE;
        }
    }


    switch ($PageMode) {
        case QUICKDRY_MODE_STATIC:
            $PageModel::Construct($Web->Request, $Web->Session, $Web->Cookie, $Web->CurrentUser, $Web->Server);
            $PageModel::DoInit();
            $Web->MasterPage = $PageModel::$MasterPage ? $PageModel::$MasterPage : null;

            if ($Web->IsSecureMasterPage()) {
                if ($Web->AccessDenied) {
                    if (!$Web->CurrentUser || !$Web->CurrentUser->id) {
                        HTTP::RedirectNotice('Please Sign In', '/signin');
                    } else {
                        HTTP::RedirectError('Access Denied (1)');
                    }
                }
            }

            switch ($Web->Verb) {
                case REQUEST_VERB_GET:
                    $PageModel::DoGet();
                    break;
                case REQUEST_VERB_POST:
                    $PageModel::DoPost();
                    break;
                case REQUEST_VERB_PUT:
                    $PageModel::DoPut();
                    break;
                case REQUEST_VERB_DELETE:
                    $PageModel::DoDelete();
                    break;
                case REQUEST_VERB_FIND:
                    $PageModel::DoFind();
                    break;
                case REQUEST_VERB_HISTORY:
                    $PageModel::DoHistory();
                    break;
            }

            if ($Web->Request->export) {
                switch (strtoupper($Web->Request->export)) {
                    case REQUEST_EXPORT_CSV:
                        $PageModel::DoExportToCSV();
                        exit;
                    case REQUEST_EXPORT_XLS:
                        $PageModel::DoExportToXLS();
                        exit;
                    case REQUEST_EXPORT_JSON:
                        $PageModel::DoExportToJSON();
                        exit;
                    case REQUEST_EXPORT_DOCX:
                        $PageModel::DoExportToDOCX();
                        $Web->RenderDOCX= true;
                        $Web->DOCXPageOrientation = $PageModel::$DOCXPageOrientation;
                        $Web->DOCXFileName = $PageModel::$DOCXFileName;
                        $Web->PDFPostRedirect = $PageModel::$PDFPostRedirect;
                        $Web->MasterPage = $PageModel::$MasterPage ? $PageModel::$MasterPage : null;
                        break;
                    case REQUEST_EXPORT_PDF:
                        $PageModel::DoExportToPDF();
                        $Web->RenderPDF = true;
                        $Web->PDFPageSize = $PageModel::$PDFPageSize;
                        $Web->PDFPageOrientation = $PageModel::$PDFPageOrientation;
                        $Web->PDFFileName = $PageModel::$PDFFileName;
                        $Web->PDFPostRedirect = $PageModel::$PDFPostRedirect;
                        $Web->MasterPage = $PageModel::$MasterPage ? $PageModel::$MasterPage : null;
                        break;
                }
            }
            break;
        case QUICKDRY_MODE_INSTANCE:
            /* @var $PageModel BasePage */
            $PageModel = new $class($Web->Request, $Web->Session, $Web->Cookie, $Web->CurrentUser, $Web->Server);
            $PageModel->Init();
            $Web->MasterPage = $PageModel->MasterPage ? $PageModel->MasterPage : null;

            if ($Web->IsSecureMasterPage()) {
                if ($Web->AccessDenied) {
                    if (!$Web->CurrentUser || !$Web->CurrentUser->id) {
                        HTTP::RedirectNotice('Please Sign In', '/signin');
                    } else {
                        if ($Web->CurrentUser) {
                            HTTP::RedirectNotice('', '/main');
                        } else {
                            HTTP::RedirectError('Access Denied (2)');
                        }
                    }
                }
            }

            switch ($Web->Verb) {
                case REQUEST_VERB_GET:
                    $PageModel->Get();
                    break;
                case REQUEST_VERB_POST:
                    $PageModel->Post();
                    break;
                case REQUEST_VERB_PUT:
                    $PageModel->Put();
                    break;
                case REQUEST_VERB_DELETE:
                    $PageModel->Delete();
                    break;
                case REQUEST_VERB_FIND:
                    $PageModel->Find();
                    break;
                case REQUEST_VERB_HISTORY:
                    $PageModel->History();
                    break;
            }

            if ($Web->Request->export) {
                switch (strtoupper($Web->Request->export)) {
                    case REQUEST_EXPORT_CSV:
                        $PageModel->ExportToCSV();
                        exit;
                    case REQUEST_EXPORT_XLS:
                        $PageModel->ExportToXLS();
                        exit;
                    case REQUEST_EXPORT_JSON:
                        $PageModel->ExportToJSON();
                        exit;
                    case REQUEST_EXPORT_PDF:
                        $PageModel->ExportToPDF();
                        $Web->RenderPDF = true;
                        $Web->PDFPageSize = $PageModel->PDFPageSize;
                        $Web->PDFPageOrientation = $PageModel->PDFPageOrientation;
                        $Web->PDFFileName = $PageModel->PDFFileName;
                        $Web->PDFPostRedirect = $PageModel->PDFPostRedirect;
                        $Web->MasterPage = $PageModel::$MasterPage ? $PageModel::$MasterPage : null;
                        break;
                    case REQUEST_EXPORT_DOCX:
                        $PageModel->ExportToDOCX();
                        $Web->RenderDOCX = true;
                        $Web->DOCXPageOrientation = $PageModel->PDFPageOrientation;
                        $Web->DOCXFileName = $PageModel->PDFFileName;
                        $Web->MasterPage = $PageModel::$MasterPage ? $PageModel::$MasterPage : null;
                        break;
                }
            }
            break;
        default:
            if ($Web->AccessDenied) {
                if (!$Web->CurrentUser || !$Web->CurrentUser->id) {
                    HTTP::RedirectNotice('Please Sign In', '/signin');
                } else {
                    HTTP::RedirectError('Access Denied (3)');
                }
            }
            if(!$Web->MasterPage) {
                $Web->MasterPage = isset($_MASTERPAGE) ? $_MASTERPAGE : MASTERPAGE_DEFAULT;
            }
    }
}

Metrics::Stop('Controller');

Metrics::Start('View');
if (file_exists($Web->ViewFile)) {
    require_once $Web->ViewFile;
} else {
    if($Web->DefaultURL) {
        HTTP::RedirectError('Page Not Found', $Web->DefaultURL);
    }
}
Metrics::Stop('View');

$Web->HTML = ob_get_clean();

if ($Web->RenderPDF) {

    ob_start();
    if (file_exists('masterpages/' . $Web->MasterPage . '.php')) {
        require_once 'masterpages/' . $Web->MasterPage . '.php';
    } else {
        Debug::Halt($Web->MasterPage . ' does not exist: ' . $Web->ViewFile);
    }
    $Web->HTML = ob_get_clean();


    Metrics::Start('render pdf');
    require_once 'QuickDRY/web/WebKit.php';

    Metrics::Stop('render pdf');
    exit;
}

if ($Web->RenderDOCX) {

    ob_start();
    if (file_exists('masterpages/' . $Web->MasterPage . '.php')) {
        require_once 'masterpages/' . $Web->MasterPage . '.php';
    } else {
        Debug::Halt($Web->MasterPage . ' does not exist: ' . $Web->ViewFile);
    }
    $Web->HTML = ob_get_clean();


    Metrics::Start('render docx');
    SimpleWordDoc::RenderHTML($Web->HTML, $Web->DOCXFileName);
    Metrics::Stop('render docx');
    exit;
}

if (file_exists('masterpages/' . $Web->MasterPage . '.php')) {
    require_once 'masterpages/' . $Web->MasterPage . '.php';
} else {
    Debug::Halt($Web->MasterPage . ' masterpage does not exist: ' . $Web->ViewFile);
}