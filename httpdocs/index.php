<?php
if (isset($_HOST)) {
    define('HTTP_HOST', $_HOST);
}

require_once 'settings.php';

require_once 'QuickDRYInstance/Defines.php';

require_once 'QuickDRY/QuickDRY.php';


require_once 'QuickDRYInstance/ChangeLogHandler.php';
require_once 'QuickDRYInstance/UserManager.php';

require_once 'common_modules.php';

define('IS_MOBILE',BrowserOS::IsMobile());
define('GUID', GUID());

ExceptionHandler::Init();


$Web = new Web();
$Web->Init('signin', 'admin', dirname(__FILE__));
$Web->SetSecureMasterPages([MASTERPAGE_DEFAULT]);



if (file_exists($Web->SettingsFile)) {
    require_once $Web->SettingsFile;
} else {
    if (file_exists('../' . $Web->SettingsFile)) {
        require_once '../' . $Web->SettingsFile;

    } else {
        if (file_exists('../httpdocs/' . $Web->SettingsFile)) {
            require_once '../httpdocs/' . $Web->SettingsFile;

        } else {
            Debug::Halt($Web->SettingsFile . ' does not exist');
        }
    }
}

if (defined('ROUTE_REQUESTS') && ROUTE_REQUESTS) {
    require_once ROUTE_REQUESTS;
    exit;
}


if ($Web->Server->REQUEST_URI) {

    require_once 'QuickDRYInstance/Menu.php';
    require_once 'QuickDRYInstance/MenuAccess.php';

    $Web->InitMenu();

    require_once 'QuickDRY/web/WebView.php';
}

