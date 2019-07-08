<?php
if (isset($_HOST)) {
    define('HTTP_HOST', $_HOST);
}

require_once 'settings.php';

require_once 'QuickDRYInstance/QuickDRYInstance.php';
require_once 'QuickDRY/QuickDRY.php';

require_once 'common_modules.php';

define('IS_MOBILE',BrowserOS::IsMobile());
define('GUID', GUID());

ExceptionHandler::Init();


$Web = new Web();
$Web->Init('signin', 'admin', dirname(__FILE__));
$Web->SetSecureMasterPages([MASTERPAGE_DEFAULT]);
$Web->SetURLs();

if (defined('ROUTE_REQUESTS') && ROUTE_REQUESTS) {
    require_once ROUTE_REQUESTS;
    exit;
}

if ($Web->Server->REQUEST_URI) {

    $Web->InitMenu();

    require_once 'QuickDRY/web/WebView.php';
}

