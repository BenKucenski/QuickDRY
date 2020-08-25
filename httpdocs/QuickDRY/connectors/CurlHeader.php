<?php

/**
 * Class CurlHeader
 */
class CurlHeader extends SimpleReport
{
    public $CacheControl;
    public $Pragma;
    public $ContentType;
    public $Expires;
    public $Server;
    public $AccessControlAllowOrigin;
    public $AccessControlAllowCredentials;
    public $AccessControlAllowMethods;
    public $AccessControlAllowHeaders;
    public $Date;
    public $ContentLength;
    public $Location;

    /**
     * CurlHeader constructor.
     * @param null $row
     */
    public function __construct($row = null)
    {
        foreach($row as $k => $v) {
            if(substr($k,0,2) == 'X-') {
                continue;
            }
            switch($k) {
                case 'Content-Length':
                    $this->ContentLength = $v;
                    break;
                case 'Date':
                    $this->Date = $v;
                    break;
                case 'Access-Control-Allow-Origin':
                    $this->AccessControlAllowOrigin = $v;
                    break;
                case 'Access-Control-Allow-Credentials':
                    $this->AccessControlAllowCredentials = $v;
                    break;
                case 'Access-Control-Allow-Methods':
                    $this->AccessControlAllowMethods = $v;
                    break;
                case 'Access-Control-Allow-Headers':
                    $this->AccessControlAllowHeaders = $v;
                    break;
                case 'Server':
                    $this->Server = $v;
                    break;
                case 'Expires':
                    $this->Expires = $v;
                    break;
                case 'Content-Type':
                    $this->ContentType = $v;
                    break;
                case 'Pragma':
                    $this->Pragma = $v;
                    break;
                case 'Cache-Control':
                    $this->CacheControl = $v;
                    break;
                case 'Location':
                    $this->Location = $v;
                    break;
                default:
                    break;
            }
        }
    }
}