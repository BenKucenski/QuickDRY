<?php
function autoloader_QuickDRY_OAuth($class)
{
    $class_map = [
        'OAuthConsumer' => 'oauth/OAuthConsumer.php',
        'OAuthDataStore' => 'oauth/OAuthDataStore.php',
        'OAuthException' => 'oauth/OAuthException.php',
        'OAuthRequest' => 'oauth/OAuthRequest.php',
        'OAuthServer' => 'oauth/OAuthServer.php',
        'OAuthSignatureMethod' => 'oauth/OAuthSignatureMethod.php',
        'OAuthSignatureMethod_HMAC_SHA1' => 'oauth/OAuthSignatureMethod_HMAC_SHA1.php',
        'OAuthSignatureMethod_PLAINTEXT' => 'oauth/OAuthSignatureMethod_PLAINTEXT.php',
        'OAuthSignatureMethod_RSA_SHA1' => 'oauth/OAuthSignatureMethod_RSA_SHA1.php',
        'OAuthToken' => 'oauth/OAuthToken.php',
        'OAuthUtil' => 'oauth/OAuthUtil.php',
    ];

    if (!isset($class_map[$class])) {
        return;
    }

    $file = $class_map[$class];
    $file = 'QuickDRY/connectors/' . $file;

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


spl_autoload_register('autoloader_QuickDRY_OAuth');


