<?php
$_NAVIGATION = new NavigationClass();

if (!$Session->user) {
    if(defined('ROLE_ID_DEFAULT')) {
        $_NAVIGATION->Combine($_MENU_ACCESS[ROLE_ID_DEFAULT]);
    }
} else {
    if(defined('ROLE_ID_DEFAULT_USER')) {
        if (isset($_MENU_ACCESS[ROLE_ID_DEFAULT_USER])) {
            $_NAVIGATION->Combine($_MENU_ACCESS[ROLE_ID_DEFAULT_USER]);

        }
    }
    if(is_array($CurrentUser->Roles)) {
        foreach ($CurrentUser->Roles as $role) {
            if (isset($_MENU_ACCESS[$role])) {
                $_NAVIGATION->Combine($_MENU_ACCESS[$role]);

            }
        }
    }
}

$_NAVIGATION->SetMenu($_MENU);

