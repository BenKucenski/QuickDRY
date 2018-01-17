<?php

/**
 * Class BasePage
 *
 * @property Request Request
 * @property Session Session
 * @property Cookie Cookie
 * @property UserClass CurrentUser
 * @property string MasterPage
 */
class BasePage extends SafeClass
{
    /* @var $Request Request */
    public static $Request;

    /* @var $Session Session */
    public static $Session;

    /* @var $Cookie Cookie */
    public static $Cookie;

    /* @var $CurrentUser UserClass */
    public static $CurrentUser;

    /* @var $IncludeMenu bool */
    public static $IncludeMenu;

    /* @var $PostData [] */
    public static $PostData;

    /* @var $Errors [] */
    protected static $Errors = [];

    /* @var $MasterPage string */
    public static $MasterPage;

    public function __get($name)
    {
        switch($name)
        {
            case 'Request':
                return static::$Request;
            case 'Session':
                return static::$Session;
            case 'Cookie':
                return static::$Cookie;
            case 'CurrentUser':
                return static::$CurrentUser;
            case 'PostData':
                return static::$PostData;
            case 'MasterPage':
                return static::$MasterPage;
            case 'Errors':
                return static::$Errors;
        }
        return parent::__get($name);
    }

    public function __set($name, $value)
    {
        switch ($name) {
            case 'Request':
                static::$Request = $value;
                break;
            case 'Session':
                static::$Session = $value;
                break;
            case 'Cookie':
                static::$Cookie = $value;
                break;
            case 'CurrentUser':
                static::$CurrentUser = $value;
                break;
            case 'PostData':
                static::$PostData = $value;
                break;
            case 'MasterPage':
                static::$MasterPage = $value;
                break;
            case 'Errors':
                static::$Errors = $value;
                break;
            default:
                return parent::__set($name, $value);
        }
        return null;
    }

    public function __construct(Request &$Request, Session &$Session, Cookie &$Cookie, UserClass &$CurrentUser = null)
    {
        static::Construct($Request, $Session, $Cookie, $CurrentUser);
    }

    public static function DoGet()
    {

    }

    public static function DoPost()
    {

    }

    public static function DoInit()
    {

    }

    public static function Construct(Request &$Request, Session &$Session, Cookie &$Cookie, UserClass &$CurrentUser = null)
    {
        static::$Request = $Request;
        static::$Cookie = $Cookie;
        static::$Session = $Session;
        static::$CurrentUser = $CurrentUser;
        static::$PostData = json_decode(file_get_contents('php://input'),false); // return a standard object
    }

    public function Get()
    {
        static::DoGet();
    }

    public function Post()
    {
        static::DoPost();
    }

    public function Init()
    {
        static::DoInit();
    }

    protected function LogError($error)
    {
        static::$Errors[] = $error;
    }

    public function HasErrors()
    {
        return sizeof(static::$Errors) ? true : false;
    }

    public function RenderErrors()
    {
        $res = '<div class="PageModelErrors"><ul>';
        foreach(static::$Errors as $error) {
            $res .= '<li>' . $error . '</li>';
        }
        $res .= '</ul></div>';
        return $res;
    }
}