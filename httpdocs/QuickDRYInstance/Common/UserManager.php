<?php
namespace QuickDRYInstance\Common;

class UserManager
{
  public function __construct()
  {
  }

    public function LogIn($username, $password)
  {
    $username = strtolower(trim($username));
    $password = trim($password);

        // $user = myUserClass::LogIn($username, $password);

        if(!is_object($user)) {
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

