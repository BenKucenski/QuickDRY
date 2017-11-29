<?php
$_NAVIGATION = new NavigationClass();

if (!$Session->user) {
    $_NAVIGATION->Combine(MenuAccess::GetForRole(ROLE_ID_DEFAULT));
} else {
    if (defined('ROLE_ID_DEFAULT_USER')) {
        $_NAVIGATION->Combine(MenuAccess::GetForRole(ROLE_ID_DEFAULT_USER));

    }
    if (is_array($CurrentUser->Roles)) {
        foreach ($CurrentUser->Roles as $role) {
            $_NAVIGATION->Combine(MenuAccess::GetForRole($role));
        }
    }
}

$_NAVIGATION->SetMenu($_MENU);

/* // example permissions checking - should be done in your index.php file or wherever this file is included
if(!$_NAVIGATION->CheckPermissions(CURRENT_PAGE, true)) {
    if(!$CurrentUser || !$CurrentUser->id) {
        RedirectNotice('Please Sign In', '/signin');
    }
    CleanHalt([$CurrentUser->Roles, MenuAccess::GetPageRoles(CURRENT_PAGE)]);
}

*/

