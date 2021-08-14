<?php
namespace QuickDRY\Connectors;

use QuickDRY\Utilities\Metrics;
use QuickDRY\Utilities\SafeClass;

class MSSQL_Queue extends SafeClass
{
    private $_sql = [];
    private $strlen = 0;
    private $MSSQL_CLASS;
    public $LogClass = 'Log';
    public $HaltOnError;
    public $IgnoreDuplicateError = false;
    private $QueueLimit;

    public function __construct($MSSQL_CLASS = 'MSSQL_A', $HaltOnError = true, $QueueLimit = 500)
    {
        $this->MSSQL_CLASS = $MSSQL_CLASS;
        $this->HaltOnError = $HaltOnError;
        $this->QueueLimit = $QueueLimit;
    }

    /**
     * @return int
     */
    public function Count()
    {
        return sizeof($this->_sql);
    }

    /**
     * @return array|null
     */
    public function Flush()
    {
        if (!$this->Count()) {
            return null;
        }

        Metrics::Toggle('MSSQL_Queue::Flush');
        $sql = implode(';' . "\n", $this->_sql);
        $sql = trim('
SET QUOTED_IDENTIFIER ON
;
        		' . $sql . ' ;');

        $class = $this->MSSQL_CLASS;
        $class::SetIgnoreDuplicateError($this->IgnoreDuplicateError);

        $res = $class::Execute($sql, null, true);

        Metrics::Toggle('MSSQL_Queue::Flush');
        if (isset($res['error']) && $res['error'] && $this->HaltOnError) {
            $LogClass = $this->LogClass;
            $LogClass::Insert(['MSSQL_Queue Error' => $res['error'], 'SQL' => $sql], true);
            exit(1);
        }

        $this->_sql = [];
        $this->strlen = 0;

        return $res;
    }

    /**
     * @param SQL_Query $sp
     * @return array|null
     */
    public function QueueSP(SQL_Query $sp): ?array
    {
        return $this->Queue($sp->SQL, $sp->Params);
    }

  /**
   * @param $sql
   * @param array $params
   * @return array|null
   */
    public function Queue($sql, array $params): ?array
    {
        $t = MSSQL::EscapeQuery($sql, $params);
        $this->_sql[] = $t;
        $this->strlen += strlen($t);

        if ($this->strlen > 1024 * 1024 * 50 || $this->Count() >= $this->QueueLimit) {
            return $this->Flush();
        }
        return ['error' => '', 'query' => ''];
    }
}