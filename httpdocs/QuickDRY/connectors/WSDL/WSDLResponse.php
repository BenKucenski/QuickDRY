<?php

use QuickDRY\Utilities\SafeClass;

class WSDLResponse extends SafeClass
{
    public ?string $Name;
    public ?array $Parameters;

    public function AddParameter(WSDLParameter $param)
    {
        if(!is_array($this->Parameters)) {
            $this->Parameters = [];
        }
        $this->Parameters[] = $param;
    }
}