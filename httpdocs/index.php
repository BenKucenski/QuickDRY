<?php
if (isset($_HOST)) {
    define('HTTP_HOST', $_HOST);
}

require_once 'settings.php';

require_once 'QuickDRYInstance/Defines.php';

require_once 'QuickDRY/QuickDRY.php';

require_once 'init.php';

ExceptionHandler::Init();

$Web = new Web();
$Web->Init('signin', 'admin');
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

if ($Web->Server->REQUEST_URI) {

    require_once 'common/Menu.php';
    require_once 'common/MenuAccess.php';

    $Web->InitMenu();

    require_once 'QuickDRY/web/WebView.php';
}

