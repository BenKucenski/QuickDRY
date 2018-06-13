<?php
$shortopts = '';
$shortopts .= 'h:';
$shortopts .= 'i:';
$shortopts .= 't:';
$shortopts .= 'f:';

$options = getopt($shortopts);

$_HOST = isset($options['h']) ? $options['h'] : '';
$_INDEX = isset($options['i']) ? $options['i'] : '';
$_TYPE = isset($options['t']) ? $options['t'] : '';
$_FILE = isset($options['f']) ? $options['f'] : '';

if (!$_HOST || !$_INDEX || !$_FILE) {
    exit('USAGE: php ' . __FILE__ . ' -h<host> -i<index> -t<type> -f<file> - json format>' . "\r\n");
}

require_once('../index.php');

class CreateIndex extends SafeClass
{
    public function __construct($_INDEX, $_TYPE, $_FILE)
    {
        $fp = fopen($_FILE,'r');
        $schema = fread($fp, filesize($_FILE));
        fclose($fp);

        $schema = json_decode($schema, true);

        $res = 'null';
        try {
            if (!$_TYPE) {
                $res = Elastic_A::CreateIndex($_INDEX, $schema);
            } else {
                // make sure main index exists
                try {
                    $res = Elastic_A::CreateIndex($_INDEX, null);
                } catch (Exception $e) {
                    // ignore this, we don't care, it just needs to exist
                    print_r($res);
                }
                $res = Elastic_A::CreateIndexType($_INDEX, $_TYPE, $schema);
            }
        } catch (Exception $e) {
            CleanHalt($e->getMessage());
        }

        CleanHalt($res);
    }
}

$cron = new CreateIndex($_INDEX, $_TYPE, $_FILE);
