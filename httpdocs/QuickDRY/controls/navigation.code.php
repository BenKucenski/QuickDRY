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

if(!$_NAVIGATION->CheckPermissions(CURRENT_PAGE, true)) {
    CleanHalt([$CurrentUser->Roles, MenuAccess::GetPageRoles(CURRENT_PAGE)]);
}

