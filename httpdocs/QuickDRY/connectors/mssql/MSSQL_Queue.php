<?php
class MSSQL_Queue
{
    private $_sql = [];
    private $strlen = 0;
    private $MSSQL_CLASS;

    public function __construct($MSSQL_CLASS = 'MSSQL_A')
    {
        $this->MSSQL_CLASS = $MSSQL_CLASS;
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
        if(!$this->Count()) {
            return null;
        }

        Metrics::Toggle('MSSQL_Queue::Flush');
        $sql = implode(PHP_EOL . ';' . PHP_EOL, $this->_sql);
        $sql = '
        		SET QUOTED_IDENTIFIER ON
        		;
        		' . $sql;

        $class = $this->MSSQL_CLASS;
        $res = $class::Execute($sql, null, true);
        Metrics::Toggle('MSSQL_Queue::Flush');
        if ($res['error']) {
            Log::Insert($res, true);
            echo $res['query'];
            exit(1);
        }

        $this->_sql = [];
        $this->strlen = 0;

        return $res;
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

        if ($this->strlen > 1024 * 1024 * 50 || $this->Count() > 500) {
            $this->Flush();
        }
    }
}