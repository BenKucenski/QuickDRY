<?php
namespace QuickDRY\Connectors;

use QuickDRY\Utilities\Debug;
use QuickDRY\Utilities\Log;
use QuickDRY\Utilities\SafeClass;

class Elastic_CodeGen extends SafeClass
{
    public string $DestinationFolder;
    public string $ConnectionClassName;
    public string $ClassPrefix;
    public array $Indexes;
    public string $ExcludeStartsWith;

    public string $ClassFolder;
    public string $ClassSchemaFolder;

    public function __construct(string $ConnectionClassName, string $ClassPrefix, $ExcludeStartsWith = [], string $DestinationFolder = '../httpdocs')
    {
        if(!class_exists($ConnectionClassName)) {
          Debug::Halt(['$ConnectionClassName ' . $ConnectionClassName . ' does not exist']);
        }

        if(!method_exists($ConnectionClassName, 'GetIndexes')) {
          Debug::Halt(['$ConnectionClassName ' . $ConnectionClassName . ' does not implement GetIndexes']);
        }

        $this->DestinationFolder = $DestinationFolder;
        $this->ConnectionClassName = $ConnectionClassName;
        $this->ClassPrefix = $ClassPrefix;
        $this->ExcludeStartsWith = $ExcludeStartsWith;

        $this->ClassFolder = $this->DestinationFolder .'/common/' . $ConnectionClassName;
        $this->ClassSchemaFolder = $this->ClassFolder . '/schema';

        if(!is_dir($this->ClassFolder)) {
            mkdir($this->ClassFolder);
        }

        if(!is_dir($this->ClassSchemaFolder)) {
            mkdir($this->ClassSchemaFolder);
        }


        $this->GetIndexes();
        $modules = $this->GenerateCode();

        $fp = fopen($this->DestinationFolder .'/includes/es_' . $this->ClassPrefix . '.php','w');


        $mod_map = [];
        foreach($modules as $mod => $file) {
            $mod_map[] = '\'' . $mod . '\' => \'' . $file . '\',';
        }
        $include_php = '<?php
/**
 * @param string $class
 */
function es_' . strtolower($this->ClassPrefix) . '_autoloader(string $class) {
    $class_map = [
        ' . implode("\r\n\t\t", $mod_map) . '
    ];

    if(!isset($class_map[$class])) {
        return;
    }

    if (file_exists($class_map[$class])) { // web
        require_once $class_map[$class];
    } else {
        if (file_exists(\'../\' . $class_map[$class])) { // cron folder
            require_once \'../\' . $class_map[$class];
        } else { // scripts folder
           require_once \''  . $this->DestinationFolder . '/\' . $class_map[$class];
        }
    }
}


spl_autoload_register(\'es_' . strtolower($this->ClassPrefix) . '_autoloader\');
        ';

        fwrite($fp, $include_php);
        fclose($fp);

    }

    private function GetIndexes()
    {
        $connection = $this->ConnectionClassName;
        $this->Indexes = $connection::GetIndexes();
        if(!is_array($this->ExcludeStartsWith) || !sizeof($this->ExcludeStartsWith)) {
            return;
        }

        foreach($this->Indexes as $index => $settings) {
            foreach($this->ExcludeStartsWith as $item) {
                $item = trim($item);
                if(!$item) {
                    continue;
                }
                if(strcasecmp(substr($index,0,strlen($item)), $item) == 0) {
                    Log::Insert([$index, $item], true);
                    unset($this->Indexes[$index]);
                }
            }
        }
    }

    private function GenerateCode()
    {
        $modules = [];

        foreach($this->Indexes as $index => $settings) {
            Log::Insert($index, true);
            $mappings = Elastic_A::GetMappings($index);

            foreach ($mappings[$index]['mappings'] as $type => $props) {
                $properties = $props['properties'];


                $classname = preg_replace('/[^a-z0-9]/si', ' ', $index . '_' . $type);
                $classname = ucwords($classname);
                $classname = str_replace(' ', '', $classname);
                $classname = $this->ClassPrefix . '_' . $classname;
                $schemaname = $classname . 'Schema';
                $classname .= 'Class';

                $prop_comments = [];
                $prop_code = [];

                $alias_get_code = '';
                $alias_set_code = '';
                $alias_list = [];
                foreach ($properties as $name => $prop_type) {
                    $alias = preg_replace('/[^a-z0-9]/si','_', $name);
                    $prop_comments[] = ' * @property ' . (isset($prop_type['properties']) ? 'object' : SQLCodeGen::ColumnTypeToProperty(preg_replace('/\(.*?\)/si', '', $prop_type['type']))) . ' ' . $name;
                    $prop_code[] = '    public $' . $alias . ';';

                    if($alias !== $name) {
                        $alias_list[$name] = $alias;

                        $alias_get_code .= '
            case \'' . $name . '\':
                return $this->' . $alias . ';
                        ';
                        $alias_set_code .= '
            case \'' . $name . '\':
                $this->' . $alias . ' = $value;
                return $value;
                        ';
                    }
                }

                $to_array_code = '';

                if(sizeof($alias_list)) {
                    $to_array_code = '
    public function ToArray($ignore_empty = false, $exclude = [])
    {
        $temp = parent::ToArray($ignore_empty, $exclude);
                    ';
                    foreach($alias_list as $name => $alias) {
                        $to_array_code .= '
        $temp[\'' . $name . '\'] = $temp[\'' . $alias . '\'];
        unset($temp[\'' . $alias . '\']);
                        ';
                    }
                    $to_array_code .= '
        return $temp;
    }
                    ';
                }
                $code = '<?php
/**
 * ' . $schemaname . '
 * @author Ben Kucenski <bkucenski@gmail.com>
 * generated by QuickDRY
 *
' . implode(PHP_EOL, $prop_comments) . '
 */

class ' . $schemaname . ' extends ' . $this->ConnectionClassName . '
{
    public static $_index = \'' . $index . '\';
    public static $_type = \'' . $type . '\';

' . implode(PHP_EOL, $prop_code) . '

    public function __get($name)
    {
        switch ($name) {
        ' . $alias_get_code . '
        }

        return parent::__get($name);
    }

    public function __set($name, $value)
    {
        switch ($name) {
        ' . $alias_set_code . '
        }

        return parent::__set($name, $value);
    }

    ' . $to_array_code . '
}
';
                $fp = fopen($this->ClassSchemaFolder . '/' . $schemaname . '.php', 'w');
                fwrite($fp, $code);
                fclose($fp);

                $code = '<?php
/**
 * ' . $classname . '
 * @author Ben Kucenski <bkucenski@gmail.com>
 * generated by QuickDRY
 *
 */

class ' . $classname . ' extends ' . $schemaname . '
{
    public function __get($name)
    {
        switch ($name) {
        }

        return parent::__get($name);
    }

    public function __set($name, $value)
    {
        switch ($name) {
        }

        return parent::__get($name);
    }

    public function Save()
    {
        return parent::Save();
    }
}
';
                if(!file_exists($this->ClassFolder . '/' .  $classname . '.php')) { // don't overwrite this file
                    $fp = fopen($this->ClassFolder . '/' . $classname . '.php', 'w');
                    fwrite($fp, $code);
                    fclose($fp);
                }

                $modules[$schemaname] = 'common/' . $this->ConnectionClassName . '/schema/' . $schemaname . '.php';
                $modules[$classname] = 'common/' . $this->ConnectionClassName . '/' .  $classname . '.php';
            }
        }

        return $modules;
    }
}