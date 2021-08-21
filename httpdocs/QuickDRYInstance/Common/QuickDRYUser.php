<?php

namespace QuickDRYInstance\Common;

use Exception;
use QuickDRY\Connectors\adLDAP;
use QuickDRY\Utilities\SafeClass;
use stdClass;

/**
 * Class UserClass
 *
 * @property string id
 * @property string Username
 * @property array Roles
 */
class QuickDRYUser extends SafeClass
{
  public ?int $id = null;
  public ?string $Username = null;
  public ?array $Roles = null;
  public ?stdClass $User = null;

  /**
   * @param $name
   * @return mixed
   */
  public function __get($name)
  {
    switch ($name) {
      case 'login':
        return $this->Username;
    }
    return $this->User->$name;
  }

  public function __set($name, $value)
  {
    $this->User->$name = $value;
  }

  public function Save(): ?array
  {
    return null;
  }

  /**
   * @param string $username
   * @param string $password
   * @return null|QuickDRYUser
   */
  public static function DoLogin(string $username, string $password): ?QuickDRYUser
  {
    // https://stackoverflow.com/questions/6222641/how-to-php-ldap-search-to-get-user-ou-if-i-dont-know-the-ou-for-base-dn
    try {
      $ldap = new adLDAP();
    } catch (Exception $ex) {
      Halt($ex);
      exit;
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
   * @return null|QuickDRYUser
   */
  public static function LogInLDAP($username, $password, $default_host = null): ?QuickDRYUser
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
   * @return string
   */
  public function GetUUID(): string
  {
    // used by change log to identify who made the change
    return $this->Username;
  }
}