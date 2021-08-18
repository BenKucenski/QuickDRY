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
   * @param        $selected
   * @param ElementID|null $id
   * @return string
   */
  public static function Select($selected, ElementID $id = null): string
  {
    return self::SelectItems(self::$_options, $selected, $id ?? new ElementID('role'), 'form-control');
  }
}