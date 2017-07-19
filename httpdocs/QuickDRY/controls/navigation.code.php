<?php
$_NAVIGATION = new NavigationClass();

if (!$Session->user) {
    $_NAVIGATION->Combine($_MENU[ROLE_ID_DEFAULT]);
} else {
    if(isset($_MENU[ROLE_ID_DEFAULT_USER])) {
        $_NAVIGATION->Combine($_MENU[ROLE_ID_DEFAULT_USER]);

    }
    foreach($CurrentUser->Roles as $role) {
        if(isset($_MENU[$role])) {
            $_NAVIGATION->Combine($_MENU[$role]);

        }
    }
}
