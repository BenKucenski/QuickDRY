<?php
define('SIMPLE_EXCEL_PROPERTY_TYPE_CALCULATED', 0);
define('SIMPLE_EXCEL_PROPERTY_TYPE_AS_GIVEN', 1);
define('SIMPLE_EXCEL_PROPERTY_TYPE_DATE', 2);
define('SIMPLE_EXCEL_PROPERTY_TYPE_DATETIME', 3);

/**
 * Class SimpleExcel_Column
 */
class SimpleExcel_Column extends SafeClass
{
    public $Header;
    public $Property;
    public $PropertyType;

    public function __construct($Header, $Property, $PropertyType = SIMPLE_EXCEL_PROPERTY_TYPE_CALCULATED)
    {
        $this->Header = $Header;
        $this->Property = $Property;
        $this->PropertyType = $PropertyType;
    }
}