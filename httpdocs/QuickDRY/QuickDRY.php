<?php
// SIMPLE_EXCEL
define('SIMPLE_EXCEL_PROPERTY_TYPE_CALCULATED', 0);
define('SIMPLE_EXCEL_PROPERTY_TYPE_AS_GIVEN', 1);
define('SIMPLE_EXCEL_PROPERTY_TYPE_DATE', 2);
define('SIMPLE_EXCEL_PROPERTY_TYPE_DATETIME', 3);
define('SIMPLE_EXCEL_PROPERTY_TYPE_CURRENCY', 4);
define('SIMPLE_EXCEL_PROPERTY_TYPE_HYPERLINK', 5);

// BasePage
define('PDF_PAGE_ORIENTATION_LANDSCAPE', 'landscape');
define('PDF_PAGE_ORIENTATION_PORTRAIT', 'portrait');

// http://doc.qt.io/archives/qt-4.8/qprinter.html#PaperSize-enum
define('PDF_PAGE_SIZE_A0', 'A0');
define('PDF_PAGE_SIZE_A1', 'A1');
define('PDF_PAGE_SIZE_A2', 'A2');
define('PDF_PAGE_SIZE_A3', 'A3');
define('PDF_PAGE_SIZE_A4', 'A4');
define('PDF_PAGE_SIZE_A5', 'A5');
define('PDF_PAGE_SIZE_A6', 'A6');
define('PDF_PAGE_SIZE_A7', 'A7');
define('PDF_PAGE_SIZE_A8', 'A8');
define('PDF_PAGE_SIZE_A9', 'A9');

define('PDF_PAGE_SIZE_B0', 'B0');
define('PDF_PAGE_SIZE_B1', 'B1');
define('PDF_PAGE_SIZE_B2', 'B2');
define('PDF_PAGE_SIZE_B3', 'B3');
define('PDF_PAGE_SIZE_B4', 'B4');
define('PDF_PAGE_SIZE_B5', 'B5');
define('PDF_PAGE_SIZE_B6', 'B6');
define('PDF_PAGE_SIZE_B7', 'B7');
define('PDF_PAGE_SIZE_B8', 'B8');
define('PDF_PAGE_SIZE_B9', 'B9');
define('PDF_PAGE_SIZE_B10', 'B10');

define('PDF_PAGE_SIZE_C5E', 'C5E');
define('PDF_PAGE_SIZE_COMM10E', 'Comm10E');
define('PDF_PAGE_SIZE_DLE', 'DLE');
define('PDF_PAGE_SIZE_EXECUTIVE', 'Executive');
define('PDF_PAGE_SIZE_FOLIO', 'Folio');
define('PDF_PAGE_SIZE_LEDGER', 'Ledger');
define('PDF_PAGE_SIZE_LEGAL', 'Legal');
define('PDF_PAGE_SIZE_LETTER', 'Letter');
define('PDF_PAGE_SIZE_TABLOID', 'Tabloid');


// HTTPStatus

define('HTTP_STATUS_CONTINUE', 100);
define('HTTP_STATUS_SWITCHING_PROTOCOLS', 101);
define('HTTP_STATUS_OK', 200);
define('HTTP_STATUS_CREATED', 201);
define('HTTP_STATUS_ACCEPTED', 202);
define('HTTP_STATUS_NON_AUTHORITATIVE_INFORMATION', 203);
define('HTTP_STATUS_NO_CONTENT', 204);
define('HTTP_STATUS_RESET_CONTENT', 205);
define('HTTP_STATUS_PARTIAL_CONTENT', 206);
define('HTTP_STATUS_MULTIPLE_CHOICES', 300);
define('HTTP_STATUS_MOVED_PERMANENTLY', 301);
define('HTTP_STATUS_FOUND', 302);
define('HTTP_STATUS_SEE_OTHER', 303);
define('HTTP_STATUS_NOT_MODIFIED', 304);
define('HTTP_STATUS_USE_PROXY', 305);
define('HTTP_STATUS_TEMPORARY_REDIRECT', 307);
define('HTTP_STATUS_BAD_REQUEST', 400);
define('HTTP_STATUS_UNAUTHORIZED', 401);
define('HTTP_STATUS_PAYMENT_REQUIRED', 402);
define('HTTP_STATUS_FORBIDDEN', 403);
define('HTTP_STATUS_NOT_FOUND', 404);
define('HTTP_STATUS_METHOD_NOT_ALLOWED', 405);
define('HTTP_STATUS_NOT_ACCEPTABLE', 406);
define('HTTP_STATUS_PROXY_AUTHENTICATION_REQUIRED', 407);
define('HTTP_STATUS_REQUEST_TIMEOUT', 408);
define('HTTP_STATUS_CONFLICT', 409);
define('HTTP_STATUS_GONE', 410);
define('HTTP_STATUS_LENGTH_REQUIRED', 411);
define('HTTP_STATUS_PRECONDITION_FAILED', 412);
define('HTTP_STATUS_PAYLOAD_TOO_LARGE', 413);
define('HTTP_STATUS_URI_TOO_LONG', 414);
define('HTTP_STATUS_UNSUPPORTED_MEDIA_TYPE', 415);
define('HTTP_STATUS_RANGE_NOT_SATISFIABLE', 416);
define('HTTP_STATUS_EXPECTATION_FAILED', 417);
define('HTTP_STATUS_UPGRADE_REQUIRED', 426);
define('HTTP_STATUS_INTERNAL_SERVER_ERROR', 500);
define('HTTP_STATUS_NOT_IMPLEMENTED', 501);
define('HTTP_STATUS_BAD_GATEWAY', 502);
define('HTTP_STATUS_SERVICE_UNAVAILABLE', 503);
define('HTTP_STATUS_GATEWAY_TIMEOUT', 504);
define('HTTP_STATUS_HTTP_VERSION_NOT_SUPPORTED', 505);

// extra
define('HTTP_STATUS_CALM_DOWN', 420);
define('HTTP_STATUS_UNPROCESSABLE_ENTITY', 422);
define('HTTP_STATUS_TOO_MANY_REQUESTS', 429);

// Web
define('QUICKDRY_MODE_STATIC', 1);
define('QUICKDRY_MODE_INSTANCE', 2);
define('QUICKDRY_MODE_BASIC', 3);

define('REQUEST_VERB_GET', 'GET');
define('REQUEST_VERB_POST', 'POST');
define('REQUEST_VERB_PUT', 'PUT');
define('REQUEST_VERB_DELETE', 'DELETE');
define('REQUEST_VERB_HISTORY', 'HISTORY');
define('REQUEST_VERB_FIND', 'FIND');

define('REQUEST_EXPORT_CSV', 'CSV');
define('REQUEST_EXPORT_PDF', 'PDF');
define('REQUEST_EXPORT_JSON', 'JSON');
define('REQUEST_EXPORT_DOCX', 'DOCX');
define('REQUEST_EXPORT_XLS', 'XLS');

// YesNo
define('SELECT_NO', 1);
define('SELECT_YES', 2);

function autoloader_QuickDRY($class)
{
    $class_map = [
        'SafeClass' => 'utilities/SafeClass.php',
        'FPDF' => 'utilities/fpdf16/fpdf.php',
        'SimpleClass' => 'utilities/SimpleClass.php',
        'Metrics' => 'utilities/Metrics.php',
        'Network' => 'utilities/Network.php',
        'LogFile' => 'utilities/LogFile.php',
        'Encrypt' => 'utilities/Encrypt.php',
        'Log' => 'utilities/Log.php',
        'Debug' => 'utilities/Debug.php',
        'Dates' => 'utilities/Dates.php',
        'HTTP' => 'utilities/HTTP.php',
        'Strings' => 'utilities/Strings.php',
        'BarcodeClass' => 'utilities/BarcodeClass.php',
        'HTMLCalendar' => 'utilities/HTMLCalendar.php',
        'Navigation' => 'utilities/Navigation.php',
        'UploadHandler' => 'utilities/UploadHandler.php',
        'Mailer' => 'utilities/Mailer.php',
        'Color' => 'utilities/Color.php',
        'SimpleReport' => 'utilities/SimpleReport.php',
        'SimpleExcel_Column' => 'utilities/SimpleExcel_Column.php',
        'SimpleExcel' => 'utilities/SimpleExcel.php',
        'SimpleExcel_Reader' => 'utilities/SimpleExcel_Reader.php',
        'ExceptionHandler' => 'utilities/ExceptionHandler.php',
        'SimpleWordDoc' => 'utilities/SimpleWordDoc.php',

        'SQLCodeGen' => 'connectors/SQLCodeGen.php',
        'ChangeLog' => 'connectors/ChangeLog.php',
        'CoreClass' => 'connectors/CoreClass.php',
        'SQL_Base' => 'connectors/SQL_Base.php',
        'SQL_Query' => 'connectors/SQL_Query.php',
        'SQL_Log' => 'connectors/SQL_Log.php',
        'Curl' => 'connectors/Curl.php',
        'WSDL' => 'connectors/WSDL.php',
        'adLDAP' => 'connectors/adLDAP.php',

        'GoogleAPI' => 'connectors/GoogleAPI.php',
        'USPSAPI' => 'connectors/USPSAPI.php',
        'APIRequest' => 'connectors/APIRequest.php',

        'BasePage' => 'web/BasePage.php',
        'Session' => 'web/Session.php',
        'Cookie' => 'web/Cookie.php',
        'Request' => 'web/Request.php',
        'Server' => 'web/Server.php',
        'BrowserOS' => 'web/BrowserOS.php',
        'Meta' => 'web/Meta.php',
        'HTTPStatus' => 'web/HTTPStatus.php',
        'Web' => 'web/Web.php',
        'PDFMargins' => 'web/PDFMargins.php',

        'FormClass' => 'web/FormClass.php',

        'JsonResult' => 'JSON/JsonResult.php',
        'JsonStatusResult' => 'JSON/JsonStatusResult.php',

        'Debt' => 'math/Debt.php',
        'PrincipalInterest' => 'math/PrincipalInterest.php',
        'MathClass' => 'math/MathClass.php',
        'UTMClass' => 'math/UTMClass.php',
        'SnowballMath' => 'math/SnowballMath.php',
        'Statistics' => 'math/Statistics.php',

    ];

    if (!isset($class_map[$class])) {
        return;
    }

    $file = $class_map[$class];
    $file = 'QuickDRY/' . $file;

    if (file_exists($file)) { // web
        require_once $file;
    } else {
        if (file_exists('../' . $file)) { // cron folder
            require_once '../' . $file;
        } else { // scripts folder
            require_once '../httpdocs/' . $file;
        }
    }
}


spl_autoload_register('autoloader_QuickDRY');


if (!class_exists('OAuth')) { // when not using the PHP OAuth extension
    require_once 'connectors/oauth.php';
}

require_once 'utilities/helpers.php';
require_once 'utilities/FineDiff.php';
require_once 'utilities/phpmailer.php';

if (!class_exists('MySQL')) {
    require_once 'connectors/MySQL.php';
}
if (!class_exists('MSSQL')) {
    require_once 'connectors/MSSQL.php';
}
if (!class_exists('MSAccess')) {
    require_once 'connectors/MSAccess.php';
}

require_once 'connectors/elastic.php';
require_once 'connectors/WSDL.php';
require_once 'connectors/twilio.php';

Metrics::StartGlobal();
BrowserOS::Configure();

// FineDiff
define('FINE_DIFF_GRANULARITY_WORD', json_encode(FineDiff::$wordGranularity));
define('FINE_DIFF_GRANULARITY_PARAGRAPH', 0);