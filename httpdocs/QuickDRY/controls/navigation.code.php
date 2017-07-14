<?php
$_NAVIGATION = new NavigationClass();

if($Session->user) {
    $_NAVIGATION->Combine($_NAV[MENU_ADMIN]);
}
if ($Session->memberID) {
    $_NAVIGATION->Combine($_NAV[MENU_RESPONDENT]);
}
if(!$Session->user && !$Session->memberID)
    {
        $_NAVIGATION->Combine($_NAV[MENU_DEFAULT]);
    }
