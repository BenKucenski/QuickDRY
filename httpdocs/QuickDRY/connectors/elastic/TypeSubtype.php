<?php
namespace elastic;

class TypeSubtype extends \SafeClass
{
    public $Type;
    public $SubType;

    public function __construct($Type = null, $SubType = null)
    {
        $this->Type = $Type;
        $this->SubType = $SubType;
    }
}