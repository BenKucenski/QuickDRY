<?php

namespace QuickDRYInstance\Common;

use QuickDRY\Utilities\ChangeLogHistoryAbstract;


class ChangeLogHistory extends ChangeLogHistoryAbstract
{
  /* @var $history ChangeLog[] */
  public ?array $changes = null;

  public static function GetHistory($DB_HOST, $database, $table, $uuid): ChangeLogHistory
  {
    $history = new ChangeLogHistory();
    $history->DB_HOST = $DB_HOST;
    $history->database = $database;
    $history->table = $table;
    $history->uuid = $uuid;
    $history->changes = [];



    return $history;
  }
}