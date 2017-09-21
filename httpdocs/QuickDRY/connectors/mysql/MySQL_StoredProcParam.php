<?php
class MySQL_StoredProcParam extends SafeClass
{
    public $StoredProc;
    public $Parameter_name;
    public $Type;
    public $Length;
    public $Prec;
    public $Scale;
    public $Param_order;
    public $Collation;

    public function __construct($row = null)
    {
        if($row) {
            $this->FromRow($row);
        }
    }
}