<?php

use QuickDRY\Utilities\SafeClass;

class WSDLParameter extends SafeClass
{
    public ?int $MinOccurs = null;
    public ?int $MaxOccurs = null;
    public ?string $Name = null;
    public ?string $Type = null;
}