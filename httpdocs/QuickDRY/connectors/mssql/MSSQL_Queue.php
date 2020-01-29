<?php

class MSSQL_Queue
{
    private $_sql = [];
    private $strlen = 0;
    private $MSSQL_CLASS;
    public $exit_on_error;
    private $queue_limit;

    public function __construct($MSSQL_CLASS = 'MSSQL_A', $exit_on_error = true, $queue_limit = 500)
    {
        $this->MSSQL_CLASS = $MSSQL_CLASS;
        $this->exit_on_error = $exit_on_error;
        $this->queue_limit = $queue_limit;
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
        $res = $class::Execute($sql, null, true);
        Metrics::Toggle('MSSQL_Queue::Flush');
        if ($res['error'] && $this->exit_on_error) {
            Log::Insert($res['error'], true);
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
    public function QueueSP(SQL_Query &$sp)
    {
        return $this->Queue($sp->SQL, $sp->Params);
    }

    /**
     * @param $sql
     * @param array $params
     */
    public function Queue($sql, $params)
    {
        $t = MSSQL::EscapeQuery($sql, $params);
        $this->_sql[] = $t;
        $this->strlen += strlen($t);

        if ($this->strlen > 1024 * 1024 * 50 || $this->Count() >= $this->queue_limit) {
            return $this->Flush();
        }
        return ['error' => '', 'query' => ''];
    }
}