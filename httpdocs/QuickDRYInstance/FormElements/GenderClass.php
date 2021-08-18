<?php
namespace QuickDRYInstance\FormElements;

use QuickDRY\Web\ElementID;
use QuickDRY\Web\FormClass;

/**
 * Class GenderClass
 */
class GenderClass extends FormClass
{
  public static array $_options = [
    'm' => 'Male',
    'f' => 'Female',
  ];

  /**
   * @param       $selected
   * @param ElementID|null $id
   *
   * @return string
   */
  public static function Select($selected, ElementID $id = null): string
  {
    if (is_null($id)) {
      $id = new ElementID('sex');
    }
    return self::SelectItems(self::$_options, $selected, $id, 'form-control');
  }
}