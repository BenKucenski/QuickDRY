<?php

use QuickDRY\Utilities\SafeClass;

/**
 * Class WSDLResult
 *
 * @property WSDLParameter[] Parameters
 */
class WSDLResult extends SafeClass
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