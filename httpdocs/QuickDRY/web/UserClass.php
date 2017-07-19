<?php
class UserClass extends SafeClass
{
    public $id;
    public $Username;
    public $Roles;

    public function __get($name)
    {
        switch($name) {
            case 'login':
                return $this->Username;
        }
        return parent::__get($name); 
    }

    public static function LogInLDAP($username, $password)
    {
        // https://stackoverflow.com/questions/6222641/how-to-php-ldap-search-to-get-user-ou-if-i-dont-know-the-ou-for-base-dn
        $ldap = new adLDAP();
        if($ldap->authenticate($username, $password)) {

            $ldamin = new adLDAP();
            $ldamin->authenticate(LDAP_ADMIN_USER, LDAP_ADMIN_PASS);

            $user = new self();
            $user->Username = $username;
            $user->id = md5($username);
            $user->Roles = $ldamin->user_groups($username, true);

            if(!sizeof($user->Roles)) {
                $username = explode('@', $username);
                $username = $username[0];
                $user->Roles = $ldamin->user_groups($username, true);
            }
            return $user;

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
}