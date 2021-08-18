<?php
namespace QuickDRYInstance\FormElements;

use QuickDRY\Web\ElementID;
use QuickDRY\Web\FormClass;

/**
 * Class MonthClass
 */
class MonthClass extends FormClass
{
  public static array $_options = [
    'null' => 'Not Set',
    '1' => 'January',
    '2' => 'February',
    '3' => 'March',
    '4' => 'April',
    '5' => 'May',
    '6' => 'June',
    '7' => 'July',
    '8' => 'August',
    '9' => 'September',
    '10' => 'October',
    '11' => 'November',
    '12' => 'December',
  ];

  /**
   * @param $id
   *
   * @return null
   */
  public static function Get($id): ?string
  {
    return self::$_options[$id] ?? null;
  }

  /**
   * @param       $selected
   * @param ElementID|null $id
   *
   * @return string
   */
  public static function Select($selected, ElementID $id = null): string
  {
    if (is_null($id)) {
      $id = new ElementID('month');
    }
    return self::SelectItems(self::$_options, $selected, $id, 'form-control');

  }
}