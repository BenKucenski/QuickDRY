<?php


namespace QuickDRY\Utilities;


abstract class ChangeLogClass
{
  public array $changes_list;
  public string $created_at;
  public abstract function GetUser();
}