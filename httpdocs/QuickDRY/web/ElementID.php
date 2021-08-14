<?php
namespace QuickDRY\Web;

use QuickDRY\Utilities\SafeClass;

class ElementID extends SafeClass
{
  public ?string $id = null;
  public ?string $name = null;

  public function __construct(string $id = null, string $name = null)
  {
    $this->id = $id ?? $name;
    $this->name = $name ?? $id;
  }

  public static function FromArray(array $row): ElementID
  {
    return new self($row['id'] ?? null, $row['name'] ?? null);
  }
}