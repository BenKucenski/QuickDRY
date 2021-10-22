<?php
namespace QuickDRY\Connectors;

use QuickDRY\Utilities\Debug;
use SQLite3;

class EasySQLite extends SQLite3
{
  public string $file;

  /**
   * EasySQLite constructor.
   * @param string $file
   */
  function __construct(string $file)
  {
    $this->file = $file;
    parent::__construct($file);
    //$this->open($file);
  }

  private function DoPreparedQuery(string $sql, array $params = null): array
  {
    $statement = $this->prepare($sql);
    foreach ($params as $k => $v) {
      $statement->bindValue(':' . $k, $v);
    }
    $res = $statement->execute();
    $list = [];
    while ($row = $res->fetchArray(SQLITE3_ASSOC)) {
      $list[] = $row;
    }
    return $list;
  }

  function DoQuery(string $sql, array $params = null): array
  {
    if ($params) {
      return $this->DoPreparedQuery($sql, $params);
    }
    $res = $this->query($sql);
    if ($this->lastErrorCode()) {
      Debug::Halt([
        'sql' => $sql
        , 'params' => $params
        , 'lastErrorMsg' => $this->lastErrorMsg()
        , 'lastErrorCode' => $this->lastErrorCode()
      ]);
    }
    $list = [];
    while ($row = $res->fetchArray(SQLITE3_ASSOC)) {
      $list[] = $row;
    }
    return $list;
  }

  function GetTables(): array
  {
    $sql = '
SELECT name FROM sqlite_master
WHERE type = :type
        ';
    $res = $this->DoQuery($sql, ['type' => 'table']);
    $tables = [];
    foreach ($res as $row) {
      $tables[] = $row['name'];
    }
    return $tables;
  }
}