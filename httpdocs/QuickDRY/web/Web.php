<?php

/**
 * Class Web
 *
 * @property string ControllerFile
 * @property string ViewFile
 * @property Request Request
 * @property Session Session
 * @property Cookie Cookie
 * @property Server Server
 * @property Navigation Navigation
 * @property bool AccessDenied
 * @property string[] SecureMasterPages
 * @property string MasterPage
 * @property string SettingsFile
 */
class Web
{
    public $ControllerFile;
    public $ViewFile;
    public $Request;
    public $Session;
    public $Cookie;
    public $Server;
    public $CurrentUser;
    public $Navigation;
    public $AccessDenied;
    public $MasterPage;
    public $SettingsFile;

    private $SecureMasterPages;

    /**
     * @param string[] $MasterPages
     */
    public function SetSecureMasterPages($MasterPages)
    {
        $this->SecureMasterPages = $MasterPages;
    }

    public function IsSecureMasterPage()
    {
        if(!is_array($this->SecureMasterPages)) {
            return false;
        }

        return in_array($this->MasterPage, $this->SecureMasterPages);
    }

    /**
     * @param string $default_page
     * @param string $default_user_page
     */
    public function Init($default_page, $default_user_page, $script_dir)
    {
        $this->Request = new Request();
        $this->Session = new Session();
        $this->Cookie = new Cookie();
        $this->Server = new Server();

        $this->CurrentUser = null;
        if ($this->Session->user) {
            $this->CurrentUser = $this->Session->user;
        }

        if(isset( $this->Server->REQUEST_URI)) {
            if (!defined('HTTP_HOST')) {
                if (isset($_SERVER['HTTP_X_FORWARDED_HOST'])) {
                    $host = explode(',', $_SERVER['HTTP_X_FORWARDED_HOST']);
                    $host = trim($host[sizeof($host) - 1]);
                } else {
                    $host = isset($_SERVER['HTTP_HOST']) && $_SERVER['HTTP_HOST'] ? $_SERVER['HTTP_HOST'] : (isset($_HOST) ? $_HOST : '');
                }

                define('HTTP_HOST', strtolower($host)); // the domain that the site needs to behave as (for proxies)
            }
        }

        $fullUrl = (HTTP::IsSecure() ? 'https://' : 'http://') . HTTP_HOST . $this->Server->REQUEST_URI;
        define('FULL_URL', $fullUrl);

        if(isset($_SERVER['SERVER_PROTOCOL'])) {
            $protocol = HTTP::IsSecure() ? 'https://' : 'http://';
            define('BASE_URL', $protocol . HTTP_HOST);
        }

        if(defined('HTTP_HOST')) {
            $this->SettingsFile = 'settings.' . HTTP_HOST . '.php';
        }

        if(defined('MYSQL_LOG') && MYSQL_LOG) {
            MySQL_Connection::$use_log = true;
        }

        if(defined('MSSQL_LOG') && MSSQL_LOG) {
            MSSQL_Connection::$use_log = true;
        }

        $t = isset($_SERVER['DOCUMENT_ROOT']) && $_SERVER['DOCUMENT_ROOT'] ? $_SERVER['DOCUMENT_ROOT'] : $script_dir;
        if($t[strlen($t) - 1] == '/') {
            $t = substr($t,0,strlen($t) - 1);
        }
        define('DOC_ROOT_PATH', $t);

        define('SORT_BY', isset($this->Request->sort_by) ? $this->Request->sort_by : null);
        define('SORT_DIR', isset($this->Request->sort_dir) ? $this->Request->sort_dir : 'asc');

        define('PAGE', isset($this->Request->page) ? $this->Request->page : 0);
        define('PER_PAGE', isset($this->Request->per_page) ? $this->Request->per_page : 20);

        $url = strtok($this->Server->REQUEST_URI, '?');

        $this->Session->last_url = $url;

        $qs = $this->Server->QUERY_STRING;
        $ru = $this->Server->REQUEST_URI;

        define('JSON_REQUEST', stristr($ru, '.json') !== false);

        $page = str_replace('?' . $qs, '', $ru);
        $page = str_replace('/' . $qs, '/', $page);

        if(strstr($page,'/') === false)
            $page .= '/';

        if($page[strlen($page) - 1] == '/') {
            $page = substr($page, 0,strlen($page) - 1);
        }

        $full_path = $page != '/' ? $page : '/';
        $t = explode('/', $full_path);
        $cur_page = $t[sizeof($t)-1];

        if(!$cur_page) {
            $cur_page = $this->CurrentUser ? $default_user_page : $default_page;
            $full_path = '/' . $cur_page;
        }

        $host = explode('.', HTTP_HOST);
        $m = sizeof($host);

        if(sizeof($host) >= 2) {
            define('URL_DOMAIN',$host[$m-2] . '.' . $host[$m-1]);
        } else {
            define('URL_DOMAIN',$host[0]);
        }

        define('COOKIE_DOMAIN','.'.URL_DOMAIN);

        define('CURRENT_PAGE', $full_path);
        define('CURRENT_PAGE_NAME', $cur_page);

        $page_alt = 'pages' . CURRENT_PAGE . '/' . CURRENT_PAGE_NAME . '.php';
        $code_alt = 'pages' . CURRENT_PAGE . '/' . CURRENT_PAGE_NAME . '.code.php';

        $page = 'pages' . CURRENT_PAGE . '.php';
        $code = 'pages' . CURRENT_PAGE . '.code.php';

        $this->ControllerFile = file_exists($code) ? $code : $code_alt;
        $this->ViewFile = file_exists($page) ? $page : $page_alt;
    }

    public function InitMenu()
    {
        $this->Navigation = new Navigation();

        if (!$this->Session->user) {
            $this->Navigation->Combine(MenuAccess::GetForRole(ROLE_ID_DEFAULT));
        } else {
            if (defined('ROLE_ID_DEFAULT_USER')) {
                $this->Navigation->Combine(MenuAccess::GetForRole(ROLE_ID_DEFAULT_USER));

            }
            if (is_array($this->CurrentUser->Roles)) {
                foreach ($this->CurrentUser->Roles as $role) {
                    $menu = MenuAccess::GetForRole($role);
                    $this->Navigation->Combine($menu);
                }
            }
        }

        $this->Navigation->SetMenu(Menu::$Menu);

        $this->AccessDenied = !$this->Navigation->CheckPermissions(CURRENT_PAGE, true);
    }
}