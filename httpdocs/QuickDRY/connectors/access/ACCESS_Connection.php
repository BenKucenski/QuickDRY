<?php
namespace QuickDRY\Connectors;

use Exception;
use PDO;
use QuickDRY\Utilities\Debug;

/**
 * Class ACCESS_Connection
 */
class ACCESS_Connection
{
  private ?PDO $conn = null;

  /**
   * @param string $file
   * @param string $user
   * @param string $pass
   * @param bool $is_dsn
   */
  public function __construct(string $file, string $user = '', string $pass = '', bool $is_dsn = false)
  {
    // one of these should work depending on which version of ACCESS Driver you have installed
    // define('ACCESS_DRIVER','odbc:Driver={Microsoft Access Driver (*.mdb, *.accdb)}');
    // define('ACCESS_DRIVER','odbc:Driver={Microsoft Access Driver (*.mdb)}');

    if (!defined('ACCESS_DRIVER')) {
      Debug::Halt('QuickDRY Error: ACCESS_DRIVER must be defined.  See source code of ACCESS_Connection.php');
      exit;
    }
    // https://www.freethinkingdesign.co.uk/blog/accessing-access-db-file-accdb-windows-64bit-via-php-using-odbc/ -- read this on 64 bit php
    // https://www.microsoft.com/en-us/download/details.aspx?id=23734 -- install this if you have 32 bit php
    // https://www.microsoft.com/en-us/download/confirmation.aspx?id=13255 -- install this if you have 64 bit php

    // https://support.microsoft.com/en-us/help/295297/prb-error-message-0x80004005-general-error-unable-to-open-registry-key

    // https://stackoverflow.com/questions/31813574/pdo-returning-error-could-not-find-driver-with-a-known-working-dsn
    // if you get an error about driver not found, check that extension=php_pdo_odbc.dll is in your php.ini
    $str = [];
    if (!$is_dsn) {
      $str[] = ACCESS_DRIVER;
      $str[] = 'Dbq=' . $file; // do not put quotes around file name even if there are spaces
    } else {
      $str[] = $file;
    }

    if ($user) {
      $str[] = 'Uid=' . $user;
    }

    if ($pass) {
      $str[] = 'PWD=' . $pass;
    }

    try {

      $this->conn = new PDO(implode(';', $str));
    } catch (Exception $ex) {
      Halt($ex);
    }
  }

  function Disconnect()
  {
    if (!is_null($this->conn)) {
      $this->conn = null;
    }
  }

  /**
   * @param string $sql
   * @param array|null $params
   * @param null $map_function
   * @return array
   */
  function Query(string $sql, ?array $params = [], $map_function = null): array
  {
    $params ??= [];
    $returnval = ['numrows' => 0, 'data' => [], 'error' => ''];
    $returnval['sql'] = $sql;
    $returnval['params'] = $params;

    $stmt = null;
    try {
      if (!$this->conn) {
        exit('Query: database failure');
      }
      $stmt = $this->conn->prepare($sql);
      if (!is_object($stmt)) {
        throw new exception('Failure to Query');
      }
      $stmt->execute($params);
    } catch (Exception $e) {
      Debug::Halt($e);
    }
    $returnval['data'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    if ($map_function) {
      $list = [];
      foreach ($returnval['data'] as $r) {
        $list[] = call_user_func($map_function, $r);
      }
      return $list;
    }
    $returnval['numrows'] = count($returnval['data']);
    if ($stmt->errorCode() != '00000') {
      $returnval['error'] = $stmt->errorCode() . ': ' . implode(', ', $stmt->errorInfo());
    }

    $stmt->closeCursor();
    $stmt = null;


    return $returnval;
  }

  /**
   * @param string $sql
   * @param array $params
   * @return array
   */
  function Execute(string $sql, array $params = []): array
  {
    $returnval = ['numrows' => 0, 'data' => [], 'error' => ''];
    $returnval['sql'] = $sql;
    $returnval['params'] = $params;

    $stmt = null;
    try {
      if (!$this->conn) {
        exit('Execute: database failure');
      }
      $stmt = $this->conn->prepare($sql);
      if (!is_object($stmt)) {
        throw new Exception('Failure to Query');
      }
      $stmt->execute($params);
    } catch (Exception $e) {
      Debug::Halt($e);
    }
    if ($stmt->errorCode() && $stmt->errorCode() != '00000') {
      $returnval['error'] = $stmt->errorCode() . ': ' . implode(', ', $stmt->errorInfo());
    }

    $stmt->closeCursor();
    $stmt = null;


    return $returnval;
  }

  public function SetDatabase()
  {
    return null;
  }

  /**
   * @return array
   */
  public function GetTables(): array
  {
    // this doesn't work as there is no permission to access MSysObjects
    // TODO: sort out how to get SCHEMA information in PHP
    $sql = '
SELECT MSysObjects.Name AS TABLE_NAME
FROM MSysObjects
WHERE (((Left([Name],1))<>"~")
        AND ((Left([Name],4))<>"MSys")
        AND ((MSysObjects.Type) In (1,4,6)))
order by MSysObjects.Name
        ';
    $res = $this->Query($sql);
    $list = [];
    foreach ($res['data'] as $row) {
      $t = $row['TABLE_NAME'];
      if (substr($t, 0, strlen('TEMP')) === 'TEMP')
        continue;

      $list[] = $t;
    }
    return $list;
  }

}