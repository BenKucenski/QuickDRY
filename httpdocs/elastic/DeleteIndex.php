<?php
// php elastic_delete_index.php -hhome -iindex -ttype

$shortopts = '';
$shortopts .= 'h:';
$shortopts .= 'i:';
$shortopts .= 't::';

$options = getopt($shortopts);

$_HOST = isset($options['h']) ? $options['h'] : '';
$_INDEX = isset($options['i']) ? $options['i'] : '';
$_TYPE = isset($options['t']) ? $options['t'] : '';

if (!$_HOST || !$_INDEX) {
    exit('USAGE: php ' . __FILE__ . ' -h<host> -i<index> -t<type>' . "\r\n");
}

require_once('../localsettings.php');
require_once('../init.php');

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