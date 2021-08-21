<?php

use QuickDRY\Utilities\HTTP;

class UserManager
{
    public function __construct()
    {
    }

    public function LogIn($username, $password): stdClass
    {
        $username = strtolower(trim($username));
        $password = trim($password);

        // $user = UserClass::LogIn($username, $password);

        if(!is_object($user)) {
            HTTP::RedirectError($user);
        }
        $u = new stdClass();
        $u->id = $user->id;
        $u->Username = $username;
        $u->Roles = $user->role_ids;
        $u->User = $user;

        return $u;
    }
}

