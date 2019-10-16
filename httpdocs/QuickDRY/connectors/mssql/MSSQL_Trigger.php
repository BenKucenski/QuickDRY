<?php

class MSSQL_Trigger extends SimpleReport
{
    public $name;
    public $object_id;
    public $parent_class;
    public $parent_class_desc;
    public $parent_id;
    public $type;
    public $type_desc;
    public $create_date;
    public $modify_date;
    public $is_ms_shipped;
    public $is_disabled;
    public $is_not_for_replication;
    public $is_instead_of_trigger;
    public $definition;
}