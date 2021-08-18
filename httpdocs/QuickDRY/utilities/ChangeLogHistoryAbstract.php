<?php


namespace QuickDRY\Utilities;


abstract class ChangeLogHistoryAbstract extends SafeClass
{
  public ?array $changes = null;

  public ?string $DB_HOST = null;
  public ?string $database = null;
  public ?string $table = null;
  public ?String $uuid = null;

}