<?php
namespace QuickDRY\Connectors;

use QuickDRY\Utilities\SafeClass;

class MySQL_StoredProcParam extends SafeClass
{
  public ?string $SPECIFIC_CATALOG;
  public ?string $SPECIFIC_SCHEMA;
  public ?string $SPECIFIC_NAME;
  public ?string $ORDINAL_POSITION;
  public ?string $PARAMETER_MODE;
  public ?string $PARAMETER_NAME;
  public ?string $DATA_TYPE;
  public ?string $CHARACTER_MAXIMUM_LENGTH;
  public ?string $CHARACTER_OCTET_LENGTH;
  public ?string $NUMERIC_PRECISION;
  public ?string $NUMERIC_SCALE;
  public ?string $DATETIME_PRECISION;
  public ?string $CHARACTER_SET_NAME;
  public ?string $COLLATION_NAME;
  public ?string $DTD_IDENTIFIER;
  public ?string $ROUTINE_TYPE;

  public function __construct($row = null)
  {
    if ($row) {
      $this->FromRow($row);
    }
  }
}