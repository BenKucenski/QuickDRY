<?php

/**
 * Class Web
 *
 * @property string ControllerFile
 * @property string ViewFile
 * @property string PageClass
 * @property Request Request
 * @property Session Session
 * @property Cookie Cookie
 * @property Server Server
 * @property Navigation Navigation
 * @property bool AccessDenied
 * @property bool IsJSON
 * @property string[] SecureMasterPages
 * @property string MasterPage
 * @property string SettingsFile
 * @property bool RenderPDF;
 * @property bool RenderDOCX;
 * @property string HTML;
 * @property string Verb
 * @property string PDFPageOrientation
 * @property string PDFPageSize
 * @property string PDFFileName
 * @property PDFMargins PDFMargins
 * @property string PDFHeader
 * @property string PDFFooter
 * @property string PDFHash
 * @property string PDFPostRedirect
 * @property bool PDFShrinkToFit
 * @property string PDFPostFunction
 * @property string PDFRootDir
 * @property string DOCXPageOrientation
 * @property string DOCXFileName
 * @property UserClass CurrentUser
 * @property string DefaultURL
 */
class Web extends SafeClass
{
    public $ControllerFile;
    public $ViewFile;
    public $PageClass;
    public $IsJSON;

    public $Request;
    public $Session;
    public $Cookie;
    public $Server;
    public $CurrentUser;
    public $Navigation;
    public $AccessDenied;
    public $MasterPage;
    public $SettingsFile;
    public $PageMode;
    public $CurrentPage;
    public $CurrentPageName;
    public $DefaultURL;

    private $SecureMasterPages;

    public $RenderPDF;
    public $PDFPageOrientation;
    public $PDFPageSize;
    public $PDFFileName;
    public $PDFPostRedirect;
    public $PDFHeader;
    public $PDFFooter;
    public $PDFSimplePageNumbers;
    public $PDFMargins;
    public $PDFPostFunction;
    public $PDFHash;
    public $PDFRootDir;
    public $PDFShrinkToFit;

    public $HTML;

    public $RenderDOCX;
    public $DOCXPageOrientation;
    public $DOCXFileName;

    public $StaticModel;
    public $InstanceModel;

    public $Verb;
    public $StartTime;
    public $InitTime;

    public $DefaultPage;
    public $DefaultUserPage;

    public $MetaTitle;
    public $MetaDescription;
    public $MetaKeywords;

    /**
     * @param string[] $MasterPages
     */
    public function SetSecureMasterPages($MasterPages)
    {
        $this->SecureMasterPages = $MasterPages;
    }

    /**
     * @return bool
     */
    public function IsSecureMasterPage()
    {
        if (!is_array($this->SecureMasterPages)) {
            return false;
        }

        return in_array($this->MasterPage, $this->SecureMasterPages);
    }

    public function __construct()
    {
        $this->StartTime = time();
        $this->RenderPDF = false;

        $this->Request = new Request();
        $this->Session = new Session();
        $this->Cookie = new Cookie();
        $this->Server = new Server();

        $this->PageMode = QUICKDRY_MODE_STATIC; // default to static classes for pages

        $this->CurrentUser = null;
        if ($this->Session->user) {
            $this->CurrentUser = $this->Session->user;
        }

        if (isset($this->Server->REQUEST_URI)) {
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

        if (defined('HTTP_HOST')) {
            $this->SettingsFile = 'settings.' . HTTP_HOST . '.php';
        }

        if (defined('MYSQL_LOG') && MYSQL_LOG) {
            MySQL_Connection::$use_log = true;
        }

        if (defined('MSSQL_LOG') && MSSQL_LOG) {
            MSSQL_Connection::$use_log = true;
        }

        if (file_exists($this->SettingsFile)) {
            require_once $this->SettingsFile;
        } else {
            if (file_exists('../' . $this->SettingsFile)) {
                require_once '../' . $this->SettingsFile;

            } else {
                if (file_exists('../httpdocs/' . $this->SettingsFile)) {
                    require_once '../httpdocs/' . $this->SettingsFile;

                } else {
                    Debug::Halt($this->SettingsFile . ' does not exist');
                }
            }
        }
    }

    /**
     * @param string $default_page
     * @param string $default_user_page
     */
    public function Init($default_page, $default_user_page, $script_dir)
    {
        $this->DefaultPage = $default_page;
        $this->DefaultUserPage = $default_user_page;

        $t = isset($_SERVER['DOCUMENT_ROOT']) && $_SERVER['DOCUMENT_ROOT'] ? $_SERVER['DOCUMENT_ROOT'] : $script_dir;
        if ($t[strlen($t) - 1] == '/') {
            $t = substr($t, 0, strlen($t) - 1);
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

        if (strstr($page, '/') === false)
            $page .= '/';

        if ($page[strlen($page) - 1] == '/') {
            $page = substr($page, 0, strlen($page) - 1);
        }

        $full_path = $page != '/' ? $page : '/';
        $t = explode('/', $full_path);
        $cur_page = $t[sizeof($t) - 1];

        if (!$cur_page) {
            $cur_page = $this->CurrentUser ? $this->DefaultUserPage : $this->DefaultPage;
            $full_path = '/' . $cur_page;
            $cur_page = explode('/', $cur_page);
            $cur_page = $cur_page[sizeof($cur_page) - 1];
        }

        $host = explode('.', HTTP_HOST);
        $m = sizeof($host);

        if (sizeof($host) >= 2) {
            define('URL_DOMAIN', $host[$m - 2] . '.' . $host[$m - 1]);
        } else {
            define('URL_DOMAIN', $host[0]);
        }

        define('COOKIE_DOMAIN', '.' . URL_DOMAIN);

        define('CURRENT_PAGE', $full_path);
        define('CURRENT_PAGE_NAME', $cur_page);

        $this->CurrentPage = $full_path;
        $this->CurrentPageName = $cur_page;

        $page_alt = 'pages' . $this->CurrentPage . '/' . $this->CurrentPageName . '.php';
        $code_alt = 'pages' . $this->CurrentPage . '/' . $this->CurrentPageName . '.code.php';

        $page = 'pages' . $this->CurrentPage . '.php';
        $code = 'pages' . $this->CurrentPage . '.code.php';


        $this->ControllerFile = file_exists($code) ? $code : (file_exists($code_alt) ? $code_alt : null);
        $this->ViewFile = file_exists($page) ? $page : (file_exists($page_alt) ? $page_alt : null);

        // Accept page.json.php and json.page.php
        $this->IsJSON = false;
        if (stristr($this->CurrentPageName, '.html') !== false) {
            $this->ControllerFile = $this->ViewFile;
            $this->ViewFile = null;
            $this->IsJSON = false;
        } else {
            if (stristr($this->CurrentPageName, '.json') !== false) {
                $this->ControllerFile = $this->ViewFile;
                $this->ViewFile = null;
                $this->IsJSON = true;
            } else {
                if (stristr($this->CurrentPageName, 'json.') !== false) {
                    $this->ControllerFile = $this->ViewFile;
                    $this->ViewFile = null;
                    $this->IsJSON = true;
                }
            }
        }

        $temp = explode('.', $this->CurrentPageName);
        $this->PageClass = $temp[0];

        $this->Verb = strtoupper($this->Request->verb ? $this->Request->verb : $this->Server->REQUEST_METHOD);
    }

    public function SetURLs()
    {
        // this must be done after the settings file is loaded to support proxy situations
        define('FULL_URL', (HTTP::IsSecure() ? 'https://' : 'http://') . HTTP_HOST . $this->Server->REQUEST_URI);

        if (isset($_SERVER['HTTPS'])) { // check if page being accessed by browser
            $protocol = HTTP::IsSecure() ? 'https://' : 'http://';

            if (!HTTP::IsSecure() && defined('FORCE_SSL') && FORCE_SSL) {
                HTTP::Redirect('https://' . HTTP_HOST);
            }

            define('BASE_URL', $protocol . HTTP_HOST);
        } else {
            if (isset($_SERVER['HTTP_HOST'])) {
                if (defined('FORCE_SSL') && FORCE_SSL) {
                    HTTP::Redirect('https://' . HTTP_HOST);
                }
            }
            if (!defined('BASE_URL')) { // allows the secure URL to be set in CRONS
                define('BASE_URL', (defined('HTTP_HOST_IS_SECURE') && HTTP_HOST_IS_SECURE ? 'https://' : 'http://') . HTTP_HOST);
            }
        }
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