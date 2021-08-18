<?php
namespace QuickDRYInstance\Common;

use Exception;

use QuickDRY\Connectors\adLDAP;
use QuickDRY\Utilities\SafeClass;

/**
 * Class UserClass
 *
 * @property int id
 * @property string Username
 * @property array Roles
 */
class UserClass extends SafeClass
{
  public int $id;
  public string $Username;
  public array $Roles;

  // replace with your actual user class
  /* @var $User mixed */
  public $User;

  /**
   * @param string $name
   * @return null|string
   */
  public function __get(string $name)
  {
    switch ($name) {
      case 'login':
        return $this->Username;
    }
    return parent::__get($name);
  }

  /**
   * @param $username
   * @param $password
   * @return null|UserClass
   */
  public static function DoLogin($username, $password): ?UserClass
  {
    // https://stackoverflow.com/questions/6222641/how-to-php-ldap-search-to-get-user-ou-if-i-dont-know-the-ou-for-base-dn

    $ldap = null;

    try {
      $ldap = new adLDAP();
    } catch (Exception $ex) {
      Halt($ex);
    }

    $ldap->set_use_tls(defined('LDAP_USE_TLS') ? LDAP_USE_TLS : false);

    try {
      if ($ldap->authenticate($username, $password)) {

        $ldadmin = null;
        // May not be necessary to use an administrative account to get role information
        if (defined('LDAP_ADMIN_USER') && LDAP_ADMIN_USER && defined('LDAP_ADMIN_PASS') && LDAP_ADMIN_PASS) {
          $ldadmin = new adLDAP();
          $ldadmin->set_use_tls(defined('LDAP_USE_TLS') ? LDAP_USE_TLS : false);
          $ldadmin->authenticate(LDAP_ADMIN_USER, LDAP_ADMIN_PASS);
        }

        $user = new self();
        $user->Username = $username;
        $user->id = md5($username);
        $user->Roles = $ldadmin ? $ldadmin->user_groups($username, true) : $ldap->user_groups($username, true);

        // user may log in with email address but roles may be linked to root account name
        if (!sizeof($user->Roles)) {
          $username = explode('@', $username);
          $username = $username[0];
          $user->Roles = $ldadmin ? $ldadmin->user_groups($username, true) : $ldap->user_groups($username, true);
        }
        return $user;

      }
    } catch (Exception $ex) {
      $err = $ex->getMessage();
      if (stristr($err, 'Invalid credentials') !== false) {
        return null;
      }
      Halt($ex);
    }
    return null;

  }

  /**
   * @param $username
   * @param $password
   * @param null $default_host
   * @return null|UserClass
   */
  public static function LogInLDAP($username, $password, $default_host = null): ?UserClass
  {
    if (!defined('LDAP_ADMIN_USER')) {
      Halt('QuickDRY Error: LDAP_ADMIN_USER must be defined in settings');
      return null;
    }

    if (!defined('LDAP_ADMIN_PASS')) {
      Halt('QuickDRY Error: LDAP_ADMIN_PASS must be defined in settings');
      return null;
    }

    if ($default_host && strstr($username, '@') === false) {
      if (!is_array($default_host)) {
        $username .= '@' . $default_host;
        return self::DoLogin($username, $password);
      }
      foreach ($default_host as $item) {
        $test = self::DoLogin($username . '@' . $item, $password);
        if ($test) {
          return $test;
        }
      }
    }

    return self::DoLogin($username, $password);
  }

  /**
   * @param $roles
   * @return bool
   */
  public function Is($roles): bool
  {
    if (!is_array($roles)) {
      $roles = [$roles];
    }
    foreach ($roles as $role) {
      if (in_array($role, $this->Roles)) {
        return true;
      }
    }
    return false;
  }

  /**
   * @return string
   */
  public function GetUUID(): string
  {
    // used by change log to identify who made the change
    return $this->Username;
  }
}