<?php

/**
 * Class BasePage
 *
 * @property Request Request
 * @property Session Session
 * @property Cookie Cookie
 *
 */
class BasePage extends SafeClass
{
    public $Request;
    public $Session;
    public $Cookie;
    public $CurrentUser;
    public $IncludeMenu;
    public $PostData;

    protected $Errors = [];

    public $MasterPage;

    public function __construct(Request &$Request, Session &$Session, Cookie &$Cookie, UserClass &$CurrentUser = null)
    {
        $this->Request = $Request;
        $this->Cookie = $Cookie;
        $this->Session = $Session;
        $this->CurrentUser = $CurrentUser;
        $this->PostData = json_decode(file_get_contents('php://input'),false); // return a standard object
    }


    public function Get()
    {
    }

    public function Post()
    {
    }

    public function Init()
    {
    }

    protected function LogError($error)
    {
        $this->Errors[] = $error;
    }

    public function HasErrors()
    {
        return sizeof($this->Errors) ? true : false;
    }

    public function RenderErrors()
    {
        $res = '<div class="PageModelErrors"><ul>';
        foreach($this->Errors as $error) {
            $res .= '<li>' . $error . '</li>';
        }
        $res .= '</ul></div>';
        return $res;
    }
}