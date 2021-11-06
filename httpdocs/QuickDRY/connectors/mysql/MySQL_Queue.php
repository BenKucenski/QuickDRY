<?php
namespace QuickDRY\Connectors;

use DateTime;
use QuickDRY\Utilities\Dates;
use QuickDRY\Utilities\Log;

class MySQL_Queue
{
  private array $_sql = [];
  public int $QueueLimit = 500;
  private int $strlen = 0;

  private string $ConnectionClass;

  public function __construct($ConnectionClass = 'MySQL_A')
  {
      $this->ConnectionClass = $ConnectionClass;
  }

  public function Count(): int
  {
    return sizeof($this->_sql);
  }

  public function Flush(): ?array
  {
    if (!$this->Count()) {
      return null;
    }

    $class = $this->ConnectionClass;

    $sql = implode(PHP_EOL . ';' . PHP_EOL, $this->_sql);
    $res = $class::Execute($sql, null, true);
    if ($res['error']) {
      Log::Insert($res, true);
      exit;
    }

    $this->_sql = [];
    $this->strlen = 0;

    return $res;
  }

  /**
   * @param $sql
   * @param $params
   *
   * @return int
   */
  public function Queue($sql, $params): int
  {
      $class = $this->ConnectionClass;

      foreach ($params as $i => $param) {
          if ($param instanceof DateTime) {
              $param = Dates::Timestamp($param);
          }
          $params[$i] = $param;
      }

      $t = $class::EscapeQuery($sql, $params);
      $this->_sql[] = $t;
      $this->strlen += strlen($t);

      if ($this->strlen > 1024 * 1024 * 50 || $this->Count() >= $this->QueueLimit) {
          $c = $this->Count();
          $this->Flush();
          return $c;
      }
      return 0;
  }
}