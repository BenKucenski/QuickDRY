<?php
class MSSQL_Queue
{
    private $_sql = [];

    public function Count()
    {
        return sizeof($this->_sql);
    }

    public function Flush()
    {
        if(!$this->Count()) {
            return;
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

        return $res;
    }

    public function Queue($sql, $params)
    {
        $this->_sql[] = ms_escape_query($sql, $params);

        if ($this->Count() > 1000) {
            $this->Flush();
        }
    }
}