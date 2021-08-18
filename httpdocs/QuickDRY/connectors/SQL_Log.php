<?php
namespace QuickDRY\Connectors;

use QuickDRY\Utilities\SafeClass;

/**
 * Class SQL_Log
 */
class SQL_Log extends SafeClass
{
    public ?string $source = null;
    public ?string $query = null;
    public ?array $params = null;
    public ?string $start_time = null;
    public ?string $end_time = null;
    public ?float $duration = null;
}