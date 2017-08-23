<?php
class ChangeLogHandler extends SafeClass
{
    public static function Save(ChangeLog &$change_log)
    {
        $cl = new elastic_ChangeLogDataClass();
        $cl->FromRow($change_log->ToArray());
        $cl->Save();
    }
}