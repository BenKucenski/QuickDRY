<?php
require_once 'utilities/SafeClass.php';
require_once 'utilities/SimpleClass.php';
require_once 'utilities/Metrics.php';
require_once 'utilities/Network.php';
require_once 'utilities/LogFile.php';
require_once 'utilities/Log.php';
require_once 'utilities/Debug.php';
require_once 'utilities/Dates.php';
require_once 'utilities/HTTP.php';
require_once 'utilities/Strings.php';
require_once 'utilities/helpers.php';
require_once 'utilities/phpmailer.php';
require_once 'utilities/BarcodeClass.php';
require_once 'utilities/HTMLCalendar.php';
require_once 'utilities/Navigation.php';
require_once 'utilities/UploadHandler.php';
require_once 'utilities/Mailer.php';
require_once 'utilities/Color.php';
require_once 'utilities/FineDiff.php';
require_once 'utilities/SimpleReport.php';
require_once 'utilities/SimpleExcel_Column.php';
require_once 'utilities/SimpleExcel.php';
require_once 'utilities/SimpleExcel_Reader.php';
require_once 'utilities/ExceptionHandler.php';

require_once 'connectors/SQLCodeGen.php';
require_once 'connectors/ChangeLog.php';
require_once 'connectors/CoreClass.php';
require_once 'connectors/SQL_Base.php';
require_once 'connectors/MySQL.php';
require_once 'connectors/MSSQL.php';
require_once 'connectors/Curl.php';
require_once 'connectors/WSDL.php';
require_once 'connectors/adLDAP.php';

if(!class_exists('OAuth')) { // when not using the PHP OAuth extension
    require_once 'connectors/oauth.php';
}

require_once 'connectors/elastic.php';
require_once 'connectors/GoogleAPI.php';
require_once 'connectors/APIRequest.php';

require_once 'web/BasePage.php';
require_once 'web/Session.php';
require_once 'web/Cookie.php';
require_once 'web/Request.php';
require_once 'web/Server.php';
require_once 'web/BrowserOS.php';
require_once 'web/FileClass.php';
require_once 'web/UserClass.php';
require_once 'web/Meta.php';
require_once 'web/HTTPStatus.php';
require_once 'web/Web.php';

require_once 'form/FormClass.php';
require_once 'form/GenderClass.php';
require_once 'form/MonthClass.php';
require_once 'form/PerPageClass.php';
require_once 'form/RoleClass.php';
require_once 'form/StatesClass.php';
require_once 'form/YesNoClass.php';

require_once 'math/Debt.php';
require_once 'math/PrincipalInterest.php';
require_once 'math/MathClass.php';
require_once 'math/UTMClass.php';
require_once 'math/SnowballMath.php';
require_once 'math/Statistics.php';