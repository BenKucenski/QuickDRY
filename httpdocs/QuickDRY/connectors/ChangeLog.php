<?php
class ChangeLog extends SafeClass
{
    public $host;
    public $database;
    public $table;
    public $uuid;
    public $changes;
    public $user_id;
    public $created_at;
    public $object_type;
    public $is_deleted;

    public function Save()
    {
        if(class_exists('ChangeLogHandler')) {
            ChangeLogHandler::Save($this);
        }
    }
}