<?php
namespace QuickDRY\Web;

use QuickDRY\Utilities\SafeClass;
use QuickDRYInstance\Common\UserClass;

/**
 * Class BasePage
 *
 */
class BasePage extends SafeClass
{
    public static ?string $PDFPageOrientation = null;
    public static ?string $PDFPageSize = null;
    public static ?bool $PDFShrinkToFit = null;
    public static ?string $PDFFileName = null;
    public static ?PDFMargins $PDFMargins = null;
    public static ?string $PDFHeader = null;
    public static ?string $PDFFooter = null;
    public static ?string $DOCXPageOrientation = null;
    public static ?string $DOCXFileName = null;
    public static ?string $PDFPostRedirect = null;
    public static ?Request $Request = null;
    public static ?Session $Session = null;
    public static ?Cookie $Cookie = null;
    public static ?Server $Server = null;
    public static ?UserClass $CurrentUser = null;
    public static ?bool $IncludeMenu = null;
    public static ?array $PostData = null;
    protected static array $Errors = [];
    public static ?string $MasterPage = null;

  /**
   * @param string $name
   * @return mixed
   */
    public function __get(string $name)
    {
        switch ($name) {
            case 'PDFMargins':
                return self::$PDFMargins;

            case 'PDFPageOrientation':
                return self::$PDFPageOrientation;

            case 'PDFPageSize':
                return self::$PDFPageSize;

            case 'PDFShrinkToFit':
                return self::$PDFShrinkToFit;

            case 'PDFFileName':
                return self::$PDFFileName;

            case 'PDFPostRedirect':
                return self::$PDFPostRedirect;

            case 'PDFHeader':
                return self::$PDFHeader;

            case 'PDFFooter':
                return self::$PDFFooter;

            case 'Request':
                return static::$Request;

            case 'Session':
                return static::$Session;

            case 'Cookie':
                return static::$Cookie;

            case 'Server':
                return static::$Server;

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
            case 'PDFMargins':
                self::$PDFMargins = $value;
                break;

            case 'PDFPageOrientation':
                self::$PDFPageOrientation = $value;
                break;

            case 'PDFPageSize':
                self::$PDFPageSize = $value;
                break;

            case 'PDFShrinkToFit':
                self::$PDFShrinkToFit = $value;
                break;

            case 'PDFFileName':
                self::$PDFFileName = $value;
                break;

            case 'PDFPostRedirect':
                self::$PDFPostRedirect = $value;
                break;

            case 'PDFHeader':
                self::$PDFHeader = $value;
                break;

            case 'PDFFooter':
                self::$PDFFooter = $value;
                break;

            case 'Request':
                static::$Request = $value;
                break;

            case 'Session':
                static::$Session = $value;
                break;

            case 'Server':
                static::$Server = $value;
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
     * @param Server|null $Server
     */
    public function __construct(Request $Request, Session $Session, Cookie $Cookie, UserClass $CurrentUser = null, Server $Server = null)
    {
        static::Construct($Request, $Session, $Cookie, $CurrentUser, $Server);
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
     * @param Server|null $Server
     */
    public static function Construct(Request $Request, Session $Session, Cookie $Cookie, UserClass $CurrentUser = null, Server $Server = null)
    {
        static::$Request = $Request;
        static::$Cookie = $Cookie;
        static::$Session = $Session;
        static::$CurrentUser = $CurrentUser;
        static::$Server = $Server;
        static::$PostData = json_decode(file_get_contents('php://input')); // return a standard object
    }

    /**
     * @return string
     */
    public static function GetClassName(): string
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
    public function HasErrors(): bool
    {
        return (bool)sizeof(static::$Errors);
    }

    /**
     * @return string
     */
    public function RenderErrors(): string
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
        Halt('QuickDRY Error: ExportToXLS is not implemented');
    }

    public function ExportToPDF()
    {
        Halt('QuickDRY Error: ExportToPDF is not implemented');
    }

    public function ExportToDOCX()
    {
        Halt('QuickDRY Error: ExportToDOCX is not implemented');
    }

    public function ExportToCSV()
    {
        Halt('QuickDRY Error: ExportToCSV is not implemented');
    }

    public function ExportToJSON()
    {
        Halt('QuickDRY Error: ExportToJSON is not implemented');
    }

    public static function DoExportToXLS()
    {
        Halt('QuickDRY Error: DoExportToXLS is not implemented');
    }

    public static function DoExportToPDF()
    {
        Halt('QuickDRY Error: DoExportToPDF is not implemented');
    }

    public static function DoExportToDOCX()
    {
        Halt('QuickDRY Error: DoExportToDOCX is not implemented');
    }

    public static function DoExportToCSV()
    {
        Halt('QuickDRY Error: DoExportToCSV is not implemented');
    }

    public static function DoExportToJSON()
    {
        Halt('QuickDRY Error: DoExportToJSON is not implemented');
    }
}