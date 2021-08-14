<?php

use QuickDRY\Utilities\SafeClass;

/**
 * Class WSDLFunction
 *
 * @property string Name
 * @property WSDLParameter[] Parameters
 * @property WSDLResult[] Returns
 * @property WSDLResult Result
 * @property WSDLResponse Response
 */
class WSDLFunction extends SafeClass
{
    public $Name;
    public $Parameters;
    public $Response;
    public $Result;
    public $Nillable;
    public $Type;

    public function AddParameter(WSDLParameter $param)
    {
        if(!is_array($this->Parameters)) {
            $this->Parameters = [];
        }
        $this->Parameters[] = $param;
    }

    public function AddResponse(WSDLParameter &$param, $name)
    {
        if(!$this->Response) {
            $this->Response = new WSDLResponse();
            $this->Response->Name = $name;
        }
        $this->Response->AddParameter($param);
    }

    public function AddResult(WSDLParameter &$param, $name)
    {
        if(!$this->Result) {
            $this->Result = new WSDLResult();
            $this->Result->Name = $name;
        }
        $this->Result->AddParameter($param);
    }
}