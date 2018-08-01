<?php
// SIMPLE_EXCEL
define('SIMPLE_EXCEL_PROPERTY_TYPE_CALCULATED', 0);
define('SIMPLE_EXCEL_PROPERTY_TYPE_AS_GIVEN', 1);
define('SIMPLE_EXCEL_PROPERTY_TYPE_DATE', 2);
define('SIMPLE_EXCEL_PROPERTY_TYPE_DATETIME', 3);
define('SIMPLE_EXCEL_PROPERTY_TYPE_CURRENCY', 4);

// BasePage
define('PDF_PAGE_ORIENTATION_LANDSCAPE', 'landscape');
define('PDF_PAGE_ORIENTATION_PORTRAIT', 'portrait');

// HTTPStatus
define('HTTP_STATUS_OK', 200);
define('HTTP_STATUS_NOT_MODIFIED', 304);
define('HTTP_STATUS_BAD_REQUEST', 400);
define('HTTP_STATUS_UNAUTHORIZED', 401);
define('HTTP_STATUS_FORBIDDEN', 403);
define('HTTP_STATUS_NOT_FOUND', 404);
define('HTTP_STATUS_NOT_ACCEPTABLE', 406);
define('HTTP_STATUS_GONE', 410);
define('HTTP_STATUS_CALM_DOWN', 420);
define('HTTP_STATUS_UNPROCESSABLE_ENTITY', 422);
define('HTTP_STATUS_TOO_MANY_REQUESTS', 429);
define('HTTP_STATUS_INTERNAL_SERVER_ERROR', 500);
define('HTTP_STATUS_BAD_GATEWAY', 502);
define('HTTP_STATUS_SERVICE_UNAVAILABLE', 503);
define('HTTP_STATUS_GATEWAY_TIMEOUT', 504);

// Web
define('QUICKDRY_MODE_STATIC', 1);
define('QUICKDRY_MODE_INSTANCE', 2);
define('QUICKDRY_MODE_BASIC', 3);

define('REQUEST_VERB_GET','GET');
define('REQUEST_VERB_POST','POST');
define('REQUEST_VERB_PUT','PUT');
define('REQUEST_VERB_DELETE','DELETE');
define('REQUEST_VERB_HISTORY','HISTORY');

define('REQUEST_VERB_FIND','FIND');
define('REQUEST_EXPORT_CSV','CSV');
define('REQUEST_EXPORT_PDF','PDF');
define('REQUEST_EXPORT_JSON','JSON');
define('REQUEST_EXPORT_XLS','XLS');

// YesNo
define('SELECT_NO', 1);
define('SELECT_YES', 2);

function autoloader_QuickDRY($class)
{
    $class_map = [
        'SafeClass' => 'utilities/SafeClass.php',
        'SimpleClass' => 'utilities/SimpleClass.php',
        'Metrics' => 'utilities/Metrics.php',
        'Network' => 'utilities/Network.php',
        'LogFile' => 'utilities/LogFile.php',
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
        'Curl' => 'connectors/Curl.php',
        'WSDL' => 'connectors/WSDL.php',
        'adLDAP' => 'connectors/adLDAP.php',

        'GoogleAPI' => 'connectors/GoogleAPI.php',
        'APIRequest' => 'connectors/APIRequest.php',

        'BasePage' => 'web/BasePage.php',
        'Session' => 'web/Session.php',
        'Cookie' => 'web/Cookie.php',
        'Request' => 'web/Request.php',
        'Server' => 'web/Server.php',
        'BrowserOS' => 'web/BrowserOS.php',
        'FileClass' => 'web/FileClass.php',
        'UserClass' => 'web/UserClass.php',
        'Meta' => 'web/Meta.php',
        'HTTPStatus' => 'web/HTTPStatus.php',
        'Web' => 'web/Web.php',

        'FormClass' => 'form/FormClass.php',
        'GenderClass' => 'form/GenderClass.php',
        'MonthClass' => 'form/MonthClass.php',
        'PerPageClass' => 'form/PerPageClass.php',
        'RoleClass' => 'form/RoleClass.php',
        'StatesClass' => 'form/StatesClass.php',
        'YesNoClass' => 'form/YesNoClass.php',

        'Debt' => 'math/Debt.php',
        'PrincipalInterest' => 'math/PrincipalInterest.php',
        'MathClass' => 'math/MathClass.php',
        'UTMClass' => 'math/UTMClass.php',
        'SnowballMath' => 'math/SnowballMath.php',
        'Statistics' => 'math/Statistics.php',

    ];

    if(!isset($class_map[$class])) {
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

require_once 'connectors/MySQL.php';
require_once 'connectors/MSSQL.php';
require_once 'connectors/elastic.php';

Metrics::StartGlobal();
BrowserOS::Configure();

// FineDiff
define('FINE_DIFF_GRANULARITY_WORD', json_encode(FineDiff::$wordGranularity));
define('FINE_DIFF_GRANULARITY_PARAGRAPH', 0);