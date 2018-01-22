<?php

/**
 * Class UserClass
 *
 * @property string id
 * @property string Username
 * @property array Roles
 */
class UserClass extends SafeClass
{
    public $id;
    public $Username;
    public $Roles;
    public $User;

    public function __get($name)
    {
        switch($name) {
            case 'login':
                return $this->Username;
        }
        return parent::__get($name); 
    }

    public static function LogInLDAP($username, $password, $default_host = null)
    {
        if($default_host && strstr($username, '@') === false) {
            $username .= '@' . $default_host;
        }

        // https://stackoverflow.com/questions/6222641/how-to-php-ldap-search-to-get-user-ou-if-i-dont-know-the-ou-for-base-dn
        $ldap = new adLDAP();
        try {
            if ($ldap->authenticate($username, $password)) {

                // May not be necessary to use an administrative account to get role information
                $ldadmin = new adLDAP();
                $ldadmin->authenticate(LDAP_ADMIN_USER, LDAP_ADMIN_PASS);

                $user = new self();
                $user->Username = $username;
                $user->id = md5($username);
                $user->Roles = $ldadmin->user_groups($username, true);

                // user may log in with email address but roles may be linked to root account name
                if (!sizeof($user->Roles)) {
                    $username = explode('@', $username);
                    $username = $username[0];
                    $user->Roles = $ldadmin->user_groups($username, true);
                }
                return $user;

            }
        } catch (Exception $ex) {
            return null;
        }
        return null;
    }

    public function Is($roles)
    {
        if(!is_array($roles)) {
            $roles = [$roles];
        }
        foreach($roles as $role) {
            if(in_array($role, $this->Roles)) {
                return true;
            }
        }
        return false;
    }

    public function GetUUID()
    {
        // used by change log to identify who made the change
        return $this->Username;
    }
}