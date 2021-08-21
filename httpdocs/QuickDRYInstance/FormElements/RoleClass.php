<?php

namespace QuickDRYInstance\FormElements;

use QuickDRY\Web\ElementID;
use QuickDRY\Web\FormClass;

/**
 * Class RoleClass
 */
class RoleClass extends FormClass
{
  public static array $_options = [
    '' => 'Not Set',
    'Administrator' => 'Site Admin',
  ];

  /**
   * @param string|null $selected
   * @param ElementID|null $id
   * @return string
   */
  public static function Select(string $selected = null, ElementID $id = null): string
  {
    if(is_null($id)) {
      $id = new ElementID('role');
    }
    return self::SelectItems(self::$_options, $selected, $id, 'form-control');
  }
}