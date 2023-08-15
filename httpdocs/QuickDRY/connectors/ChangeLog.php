<?php
namespace QuickDRY\Connectors;

use QuickDRY\Utilities\SafeClass;
use QuickDRYInstance\Common\ChangeLogHandler;

/**
 * Class ChangeLog
 */
class ChangeLog extends SafeClass
{
    public string $host;
    public ?string $database;
    public ?string $table;
    public string $uuid;
    public string $changes;
    public ?int $user_id;
    public ?string $created_at;
    public string $object_type;
    public bool $is_deleted;

    public function Save()
    {
        if(class_exists('QuickDRYInstance\Common\ChangeLogHandler')) {
          ChangeLogHandler::Save($this);
        }
    }
}