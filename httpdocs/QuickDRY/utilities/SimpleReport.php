<?php
namespace QuickDRY\Utilities;

/**
 * Class SimpleReport
 */
class SimpleReport extends SafeClass
{
  /**
   * SimpleReport constructor.
   * @param null $row
   */
  public function __construct($row = null)
  {
    if ($row) {
      $this->HaltOnError(false);
      $this->FromRow($row);
      if ($this->HasMissingProperties()) {
        Halt($this->GetMissingProperties());
      }
      $this->HaltOnError(true);
    }
  }
}