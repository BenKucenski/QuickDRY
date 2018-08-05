<?php
/**
 * Class BasePage
 *
 */
class BasePage extends SafeClass
{
    /* @var string $PDFPageOrientation */
    public static $PDFPageOrientation;

    /* @var string $PDFPageSize */
    public static $PDFPageSize;

    /* @var string $PDFFileName */
    public static $PDFFileName;

    /* @var string $PDFPostRedirect */
    public static $PDFPostRedirect;

    /* @var Request $Request */
    public static $Request;

    /* @var Session $Session */
    public static $Session;

    /* @var Cookie $Cookie */
    public static $Cookie;

    /* @var UserClass $CurrentUser */
    public static $CurrentUser;

    /* @var bool $IncludeMenu */
    public static $IncludeMenu;

    /* @var [] $PostData */
    public static $PostData;

    /* @var $Errors [] */
    protected static $Errors = [];

    /* @var $MasterPage string */
    public static $MasterPage;

    /**
     * @param $name
     * @return array|Cookie|null|Request|Session|string|UserClass
     */
    public function __get($name)
    {
        switch ($name) {
            case 'PDFPageOrientation':
                return self::$PDFPageOrientation;

            case 'PDFPageSize':
                return self::$PDFPageSize;

            case 'PDFFileName':
                return self::$PDFFileName;

            case 'PDFPostRedirect':
                return self::$PDFPostRedirect;

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

    /**
     * @param $name
     * @param $value
     * @return mixed|null
     */
    public function __set($name, $value)
    {
        switch ($name) {
            case 'PDFPageOrientation':
                self::$PDFPageOrientation = $value;
                break;

            case 'PDFPageSize':
                self::$PDFPageSize = $value;
                break;

            case 'PDFFileName':
                self::$PDFFileName = $value;
                break;

            case 'PDFPostRedirect':
                self::$PDFPostRedirect = $value;
                break;

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

    /**
     * BasePage constructor.
     * @param Request $Request
     * @param Session $Session
     * @param Cookie $Cookie
     * @param UserClass|null $CurrentUser
     */
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

    public static function DoPut()
    {

    }

    public static function DoDelete()
    {

    }

    public static function DoFind()
    {

    }

    public static function DoHistory()
    {

    }

    /**
     * @param Request $Request
     * @param Session $Session
     * @param Cookie $Cookie
     * @param UserClass|null $CurrentUser
     */
    public static function Construct(Request &$Request, Session &$Session, Cookie &$Cookie, UserClass &$CurrentUser = null)
    {
        static::$Request = $Request;
        static::$Cookie = $Cookie;
        static::$Session = $Session;
        static::$CurrentUser = $CurrentUser;
        static::$PostData = json_decode(file_get_contents('php://input'), false); // return a standard object
    }

    /**
     * @return string
     */
    public static function GetClassName()
    {
        return get_called_class();
    }

    public function Get()
    {
        static::DoGet();
    }

    public function Post()
    {
        static::DoPost();
    }

    public function Put()
    {
        static::DoPut();
    }

    public function Delete()
    {
        static::DoDelete();
    }

    public function Find()
    {
        static::DoFind();
    }

    public function Init()
    {
        static::DoInit();
    }

    public function History()
    {
        static::DoHistory();
    }


    /**
     * @param $error
     */
    protected function LogError($error)
    {
        static::$Errors[] = $error;
    }

    /**
     * @return bool
     */
    public function HasErrors()
    {
        return sizeof(static::$Errors) ? true : false;
    }

    /**
     * @return string
     */
    public function RenderErrors()
    {
        $res = '<div class="PageModelErrors"><ul>';
        foreach (static::$Errors as $error) {
            $res .= '<li>' . $error . '</li>';
        }
        $res .= '</ul></div>';
        return $res;
    }

    public function ExportToXLS()
    {
        Halt('ExportToXLS is not implemented');
    }

    public function ExportToPDF()
    {
        Halt('ExportToPDF is not implemented');
    }

    public function ExportToCSV()
    {
        Halt('ExportToCSV is not implemented');
    }

    public function ExportToJSON()
    {
        Halt('ExportToJSON is not implemented');
    }

    public static function DoExportToXLS()
    {
        Halt('DoExportToXLS is not implemented');
    }

    public static function DoExportToPDF()
    {
        Halt('DoExportToPDF is not implemented');
    }

    public static function DoExportToCSV()
    {
        Halt('DoExportToCSV is not implemented');
    }

    public static function DoExportToJSON()
    {
        Halt('DoExportToJSON is not implemented');
    }
}