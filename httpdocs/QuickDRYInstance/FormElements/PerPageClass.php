<?php

namespace QuickDRYInstance\FormElements;

use QuickDRY\Web\ElementID;
use QuickDRY\Web\FormClass;

/**
 * Class PerPageClass
 */
class PerPageClass extends FormClass
{
  public static array $_options = [
    20 => '20',
    50 => '50',
    100 => '100',
    250 => '250',
  ];

  /**
   * @param string|null $selected
   * @param ElementID|null $id
   * @param string $onchange
   * @return string
   */
  public static function Select(
    string $selected = null,
    ElementID $id = null,
    string $onchange = ''): string
  {
    if(is_null($id)) {
      $id = new ElementID('per_page');
    }
    return self::SelectItems(self::$_options, $selected, $id, $onchange);
  }
}