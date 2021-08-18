<?php

use QuickDRY\Utilities\SafeClass;

class JsonResult extends SafeClass
{
    public ?string $ContentEncoding = null;
    public ?string $ContentType = null;
    public ?string $Data = null;
    public ?string $JsonRequestBehavior = null;
    public ?int $MaxJsonLength = null;
    public ?int $RecursionLimit = null;

    public function __construct()
    {
    }
}