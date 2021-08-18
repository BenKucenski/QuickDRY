<?php

namespace QuickDRYInstance\Common;

use QuickDRY\Utilities\HTTP;

class UserManager
{
  public function LogIn(string $username, string $password): UserClass
  {
    $username = strtolower(trim($username));
    $password = trim($password);

    // $user = myUserClass::LogIn($username, $password);

    $user = null;

    if (!is_object($user)) {
      HTTP::RedirectError($user);
    }
    $u = new UserClass();
    $u->id = $user->id;
    $u->Username = $username;
    $u->Roles = $user->user_role_ids;
    $u->User = $user;

    return $u;
  }
}

