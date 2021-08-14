<?php
namespace QuickDRY\Utilities;

/**
 * Class SimpleExcel_Column
 */
class SimpleExcel_Column extends SafeClass
{
    public ?string $Header;
    public ?string $Property;
    public int $PropertyType;

  /**
   * SimpleExcel_Column constructor.
   * @param string|null $Header
   * @param string|null $Property
   * @param int $PropertyType
   */
    public function __construct(
      string $Header = null,
      string $Property = null,
      int $PropertyType = SIMPLE_EXCEL_PROPERTY_TYPE_CALCULATED)
    {
        $this->Header = is_null($Header) ? $Property : $Header;
        $this->Property = is_null($Property) ? $Header : $Property;
        $this->PropertyType = $PropertyType;
    }

    /**
     * @param $PropertyType
     */
    public function SetPropertyType($PropertyType)
    {
        $this->PropertyType = $PropertyType;
    }
}