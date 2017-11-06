<?php
class MSSQL_Queue
{
    private $_sql = [];
    private $strlen = 0;

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

        $sql = implode(PHP_EOL . ';' . PHP_EOL, $this->_sql);
        $sql = '
        		SET QUOTED_IDENTIFIER ON
        		;
        		' . $sql;

        $res = MSSQL_A::Execute($sql, null, true);
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
        $t = ms_escape_query($sql, $params);
        $this->_sql[] = $t;
        $this->strlen += strlen($t);

        if ($this->strlen > 1024 * 1024 * 50 || $this->Count() > 500) {
            $this->Flush();
        }
    }
}