<?php
namespace QuickDRYInstance\FormElements;

use QuickDRY\Web\ElementID;
use QuickDRY\Web\FormClass;

/**
 * Class YesNoClass
 */
class YesNoClass extends FormClass
{
  public static array $_options = [
    '0' => 'Not Set',
    '2' => 'Yes',
    '1' => 'No',
  ];

  /**
   * @param        $selected
   * @param ElementID|null $id
   * @return string
   */
  public static function Select($selected, ElementID $id = null): string
  {
    if (is_null($id)) {
      $id = new ElementID('yesno');
    }
    return self::SelectItems(self::$_options, $selected, $id, 'form-control');
  }
}