<?php

/**
 * Class SQL_Log
 */
class SQL_Log extends SafeClass
{
    public $source;
    public $query;
    public $params;
    public $start_time;
    public $end_time;
    public $duration;
}