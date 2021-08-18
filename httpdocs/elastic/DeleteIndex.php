<?php
// php elastic_delete_index.php -hhome -iindex -ttype

use QuickDRY\Connectors\Elastic_A;
use QuickDRY\Utilities\Log;
use QuickDRY\Utilities\SafeClass;

$shortopts = 'h:';
$shortopts .= 'i:';
$shortopts .= 't::';

$options = getopt($shortopts);

$_HOST = $options['h'] ?? '';
$_INDEX = $options['i'] ?? '';
$_TYPE = $options['t'] ?? '';

if (!$_HOST || !$_INDEX) {
    exit('USAGE: php ' . __FILE__ . ' -h<host> -i<index> -t<type>' . "\r\n");
}

require_once('../index.php');

class DeleteIndex extends SafeClass
{
    public function __construct($_INDEX, $_TYPE)
    {
        Log::Insert('Removing Index ' . $_INDEX . ', Type ' . $_TYPE, true);
        $res = 'null';
        try {
            // note that in Elastic 5.5 it's no longer possible to delete just one type
            // you must delete the entire index
            if ($_TYPE) {
                $res = Elastic_A::DeleteIndexType($_INDEX, $_TYPE);

            } else {
                $res = Elastic_A::DeleteIndex($_INDEX);
            }
            Log::Insert('Success', true);
        } catch (Exception $e) {
            Log::Insert($e->getMessage(), true);
        }


    }
}


$cron = new DeleteIndex($_INDEX, $_TYPE);