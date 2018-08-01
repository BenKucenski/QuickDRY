<?php
/**
 * Class SimpleExcel_Column
 */
class SimpleExcel_Column extends SafeClass
{
    public $Header;
    public $Property;
    public $PropertyType;

    /**
     * SimpleExcel_Column constructor.
     * @param $Header
     * @param $Property
     * @param int $PropertyType
     */
    public function __construct($Header, $Property, $PropertyType = SIMPLE_EXCEL_PROPERTY_TYPE_CALCULATED)
    {
        $this->Header = is_null($Header) ? $Property : $Header;
        $this->Property = $Property;
        $this->PropertyType = $PropertyType;
    }
}