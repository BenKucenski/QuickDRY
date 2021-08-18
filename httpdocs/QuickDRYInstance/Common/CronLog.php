<?php
namespace QuickDRYInstance\Common;



use QuickDRY\Utilities\Log;
use QuickDRY\Utilities\SafeClass;

class CronLog extends SafeClass
{
    public static function Insert($message, bool $compatible = true)
    {
        if (!defined('IS_PRODUCTION') || !IS_PRODUCTION) {
            Log::Insert($message, true);
        } else {
            Log::Insert($message, true, false);
        }
    }
}