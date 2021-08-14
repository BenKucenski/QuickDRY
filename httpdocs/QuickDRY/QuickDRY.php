<?php

// SIMPLE_EXCEL
use QuickDRY\Utilities\Metrics;
use QuickDRY\Web\BrowserOS;

const SIMPLE_EXCEL_PROPERTY_TYPE_CALCULATED = 0;
const SIMPLE_EXCEL_PROPERTY_TYPE_AS_GIVEN = 1;
const SIMPLE_EXCEL_PROPERTY_TYPE_DATE = 2;
const SIMPLE_EXCEL_PROPERTY_TYPE_DATETIME = 3;
const SIMPLE_EXCEL_PROPERTY_TYPE_CURRENCY = 4;
const SIMPLE_EXCEL_PROPERTY_TYPE_HYPERLINK = 5;

// BasePage
const PDF_PAGE_ORIENTATION_LANDSCAPE = 'landscape';
const PDF_PAGE_ORIENTATION_PORTRAIT = 'portrait';

// http://doc.qt.io/archives/qt-4.8/qprinter.html#PaperSize-enum
const PDF_PAGE_SIZE_A0 = 'A0';
const PDF_PAGE_SIZE_A1 = 'A1';
const PDF_PAGE_SIZE_A2 = 'A2';
const PDF_PAGE_SIZE_A3 = 'A3';
const PDF_PAGE_SIZE_A4 = 'A4';
const PDF_PAGE_SIZE_A5 = 'A5';
const PDF_PAGE_SIZE_A6 = 'A6';
const PDF_PAGE_SIZE_A7 = 'A7';
const PDF_PAGE_SIZE_A8 = 'A8';
const PDF_PAGE_SIZE_A9 = 'A9';

const PDF_PAGE_SIZE_B0 = 'B0';
const PDF_PAGE_SIZE_B1 = 'B1';
const PDF_PAGE_SIZE_B2 = 'B2';
const PDF_PAGE_SIZE_B3 = 'B3';
const PDF_PAGE_SIZE_B4 = 'B4';
const PDF_PAGE_SIZE_B5 = 'B5';
const PDF_PAGE_SIZE_B6 = 'B6';
const PDF_PAGE_SIZE_B7 = 'B7';
const PDF_PAGE_SIZE_B8 = 'B8';
const PDF_PAGE_SIZE_B9 = 'B9';
const PDF_PAGE_SIZE_B10 = 'B10';

const PDF_PAGE_SIZE_C5E = 'C5E';
const PDF_PAGE_SIZE_COMM10E = 'Comm10E';
const PDF_PAGE_SIZE_DLE = 'DLE';
const PDF_PAGE_SIZE_EXECUTIVE = 'Executive';
const PDF_PAGE_SIZE_FOLIO = 'Folio';
const PDF_PAGE_SIZE_LEDGER = 'Ledger';
const PDF_PAGE_SIZE_LEGAL = 'Legal';
const PDF_PAGE_SIZE_LETTER = 'Letter';
const PDF_PAGE_SIZE_TABLOID = 'Tabloid';


// HTTPStatus

const HTTP_STATUS_CONTINUE = 100;
const HTTP_STATUS_SWITCHING_PROTOCOLS = 101;
const HTTP_STATUS_OK = 200;
const HTTP_STATUS_CREATED = 201;
const HTTP_STATUS_ACCEPTED = 202;
const HTTP_STATUS_NON_AUTHORITATIVE_INFORMATION = 203;
const HTTP_STATUS_NO_CONTENT = 204;
const HTTP_STATUS_RESET_CONTENT = 205;
const HTTP_STATUS_PARTIAL_CONTENT = 206;
const HTTP_STATUS_MULTIPLE_CHOICES = 300;
const HTTP_STATUS_MOVED_PERMANENTLY = 301;
const HTTP_STATUS_FOUND = 302;
const HTTP_STATUS_SEE_OTHER = 303;
const HTTP_STATUS_NOT_MODIFIED = 304;
const HTTP_STATUS_USE_PROXY = 305;
const HTTP_STATUS_TEMPORARY_REDIRECT = 307;
const HTTP_STATUS_BAD_REQUEST = 400;
const HTTP_STATUS_UNAUTHORIZED = 401;
const HTTP_STATUS_PAYMENT_REQUIRED = 402;
const HTTP_STATUS_FORBIDDEN = 403;
const HTTP_STATUS_NOT_FOUND = 404;
const HTTP_STATUS_METHOD_NOT_ALLOWED = 405;
const HTTP_STATUS_NOT_ACCEPTABLE = 406;
const HTTP_STATUS_PROXY_AUTHENTICATION_REQUIRED = 407;
const HTTP_STATUS_REQUEST_TIMEOUT = 408;
const HTTP_STATUS_CONFLICT = 409;
const HTTP_STATUS_GONE = 410;
const HTTP_STATUS_LENGTH_REQUIRED = 411;
const HTTP_STATUS_PRECONDITION_FAILED = 412;
const HTTP_STATUS_PAYLOAD_TOO_LARGE = 413;
const HTTP_STATUS_URI_TOO_LONG = 414;
const HTTP_STATUS_UNSUPPORTED_MEDIA_TYPE = 415;
const HTTP_STATUS_RANGE_NOT_SATISFIABLE = 416;
const HTTP_STATUS_EXPECTATION_FAILED = 417;
const HTTP_STATUS_UPGRADE_REQUIRED = 426;
const HTTP_STATUS_INTERNAL_SERVER_ERROR = 500;
const HTTP_STATUS_NOT_IMPLEMENTED = 501;
const HTTP_STATUS_BAD_GATEWAY = 502;
const HTTP_STATUS_SERVICE_UNAVAILABLE = 503;
const HTTP_STATUS_GATEWAY_TIMEOUT = 504;
const HTTP_STATUS_HTTP_VERSION_NOT_SUPPORTED = 505;

// extra
const HTTP_STATUS_CALM_DOWN = 420;
const HTTP_STATUS_UNPROCESSABLE_ENTITY = 422;
const HTTP_STATUS_TOO_MANY_REQUESTS = 429;

// Web
const QUICKDRY_MODE_STATIC = 1;
const QUICKDRY_MODE_INSTANCE = 2;
const QUICKDRY_MODE_BASIC = 3;

const REQUEST_VERB_GET = 'GET';
const REQUEST_VERB_POST = 'POST';
const REQUEST_VERB_PUT = 'PUT';
const REQUEST_VERB_DELETE = 'DELETE';
const REQUEST_VERB_HISTORY = 'HISTORY';
const REQUEST_VERB_FIND = 'FIND';

const REQUEST_EXPORT_CSV = 'CSV';
const REQUEST_EXPORT_PDF = 'PDF';
const REQUEST_EXPORT_JSON = 'JSON';
const REQUEST_EXPORT_DOCX = 'DOCX';
const REQUEST_EXPORT_XLS = 'XLS';

// YesNo
const SELECT_NO = 1;
const SELECT_YES = 2;

function autoloader_QuickDRY($class)
{
  $class_map = [
    'FPDF' => 'utilities/fpdf16/fpdf.php',

    'QuickDRY\Utilities\SafeClass' => 'utilities/SafeClass.php',
    'QuickDRY\Utilities\SimpleClass' => 'utilities/SimpleClass.php',
    'QuickDRY\Utilities\Metrics' => 'utilities/Metrics.php',
    'QuickDRY\Utilities\Network' => 'utilities/Network.php',
    'QuickDRY\Utilities\LogFile' => 'utilities/LogFile.php',
    'QuickDRY\Utilities\Encrypt' => 'utilities/Encrypt.php',
    'QuickDRY\Utilities\Log' => 'utilities/Log.php',
    'QuickDRY\Utilities\Debug' => 'utilities/Debug.php',
    'QuickDRY\Utilities\Dates' => 'utilities/Dates.php',
    'QuickDRY\Utilities\HTTP' => 'utilities/HTTP.php',
    'QuickDRY\Utilities\Strings' => 'utilities/Strings.php',
    'QuickDRY\Utilities\BarcodeClass' => 'utilities/BarcodeClass.php',
    'QuickDRY\Utilities\HTMLCalendar' => 'utilities/HTMLCalendar.php',
    'QuickDRY\Utilities\Navigation' => 'utilities/Navigation.php',
    'QuickDRY\Utilities\UploadHandler' => 'utilities/UploadHandler.php',
    'QuickDRY\Utilities\Mailer' => 'utilities/Mailer.php',
    'QuickDRY\Utilities\Color' => 'utilities/Color.php',
    'QuickDRY\Utilities\SimpleReport' => 'utilities/SimpleReport.php',
    'QuickDRY\Utilities\SimpleExcel_Column' => 'utilities/SimpleExcel_Column.php',
    'QuickDRY\Utilities\SimpleExcel' => 'utilities/SimpleExcel.php',
    'QuickDRY\Utilities\SimpleExcel_Reader' => 'utilities/SimpleExcel_Reader.php',
    'QuickDRY\Utilities\ExceptionHandler' => 'utilities/ExceptionHandler.php',
    'QuickDRY\Utilities\SimpleWordDoc' => 'utilities/SimpleWordDoc.php',
    'QuickDRY\Utilities\ChangeLog' => 'utilities/ChangeLog.php',

    'QuickDRY\Connectors\ChangeLog' => 'connectors/ChangeLog.php',

    'SQLCodeGen' => 'connectors/SQLCodeGen.php',
    'CoreClass' => 'connectors/CoreClass.php',
    'SQL_Base' => 'connectors/SQL_Base.php',
    'SQL_Query' => 'connectors/SQL_Query.php',
    'SQL_Log' => 'connectors/SQL_Log.php',
    'CurlHeader' => 'connectors/CurlHeader.php',
    'Curl' => 'connectors/Curl.php',
    'WSDL' => 'connectors/WSDL.php',
    'adLDAP' => 'connectors/adLDAP.php',

    'GoogleAPI' => 'connectors/GoogleAPI.php',
    'USPSAPI' => 'connectors/USPSAPI.php',
    'APIRequest' => 'connectors/APIRequest.php',

    'QuickDRY\Web\BasePage' => 'web/BasePage.php',
    'QuickDRY\Web\Session' => 'web/Session.php',
    'QuickDRY\Web\Cookie' => 'web/Cookie.php',
    'QuickDRY\Web\Request' => 'web/Request.php',
    'QuickDRY\Web\Server' => 'web/Server.php',
    'QuickDRY\Web\BrowserOS' => 'web/BrowserOS.php',
    'QuickDRY\Web\Meta' => 'web/Meta.php',
    'QuickDRY\Web\HTTPStatus' => 'web/HTTPStatus.php',
    'QuickDRY\Web\Web' => 'web/Web.php',
    'QuickDRY\Web\PDFMargins' => 'web/PDFMargins.php',

    'QuickDRY\Web\FormClass' => 'web/FormClass.php',

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
      if (file_exists('../httpdocs/' . $file)) {
        require_once '../httpdocs/' . $file;
      } else {
        ob_start();
        debug_print_backtrace();
        $trace = ob_get_contents();
        ob_end_clean();

        // Remove first item from backtrace as it's this function which
        // is redundant.
        if (strlen($trace) < 1024 * 64) {
          $trace = preg_replace('/^#0\s+' . __FUNCTION__ . "[^\n]*\n/", '', $trace, 1);
        }

        exit($trace);
      }
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
const FINE_DIFF_GRANULARITY_PARAGRAPH = 0;