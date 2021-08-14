<?php

use QuickDRY\Utilities\SafeClass;

class WSDLResponse extends SafeClass
{
    public $Name;
    public $Parameters;

    public function AddParameter(WSDLParameter $param)
    {
        if(!is_array($this->Parameters)) {
            $this->Parameters = [];
        }
        $this->Parameters[] = $param;
    }
}