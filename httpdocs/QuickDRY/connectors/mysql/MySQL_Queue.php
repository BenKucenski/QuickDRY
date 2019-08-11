<?php
class MySQL_Queue
{
    private $_sql = [];
    private $_params = [];

    public function Count()
    {
        return sizeof($this->_sql);
    }

    public function Flush()
    {
        if(!$this->Count()) {
            return null;
        }

        $sql = implode(PHP_EOL . ';' . PHP_EOL, $this->_sql);
        $res = MySQL_A::Execute($sql, $this->_params, true);
        if ($res['error']) {
            Log::Insert($res, true);
            exit;
        }

        $this->_sql = [];
        $this->_params = [];

        return $res;
    }

    /**
     * @param $sql
     * @param $params
     *
     * @return int
     */
    public function Queue($sql, $params)
    {
        $this->_sql[] = $sql;
        foreach ($params as $param) {
            if($param instanceof DateTime) {
		        $param = Dates::Timestamp($param);
		    }
            $this->_params[] = $param;
        }

        if ($this->Count() > 500 || sizeof($this->_params) > 1000 ) {
            $c = $this->Count();
            $this->Flush();
            return $c;
        }
        return 0;
    }
}