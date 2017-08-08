<?php
class Elastic_CodeGen extends SafeClass
{
    public $ConnectionClassName;
    public $ClassPrefix;
    public $Indexes;
    public $SchemaPath;
    public $ClassPath;
    public $ExcludeStartsWith;

    public function __construct($ConnectionClassName, $ClassPrefix, $ExcludeStartsWith = [])
    {
        if(!class_exists($ConnectionClassName)) {
            CleanHalt(['$ConnectionClassName ' . $ConnectionClassName . ' does not exist']);
        }

        if(!method_exists($ConnectionClassName, 'GetIndexes')) {
            CleanHalt(['$ConnectionClassName ' . $ConnectionClassName . ' does not implement GetIndexes']);
        }

        if (!is_dir('includes')) {
            mkdir('includes');
        }

        if(!is_dir('elastic')) {
            mkdir('elastic');
        }

        if(!is_dir('elastic_schema')) {
            mkdir('elastic_schema');
        }

        $this->ClassPath = 'elastic/' . $ConnectionClassName;
        if(!is_dir($this->ClassPath)) {
            mkdir($this->ClassPath);
        }

        $this->SchemaPath = 'elastic_schema/' . $ConnectionClassName;
        if(!is_dir($this->SchemaPath)) {
            mkdir($this->SchemaPath);
        }

        $this->SchemaPath = 'elastic_schema/' . $ConnectionClassName . '/schema';
        if(!is_dir($this->SchemaPath)) {
            mkdir($this->SchemaPath);
        }

        $this->ConnectionClassName = $ConnectionClassName;
        $this->ClassPrefix = $ClassPrefix;

        $this->ExcludeStartsWith = $ExcludeStartsWith;

        $this->GetIndexes();
        $modules = $this->GenerateCode();

        $fp = fopen('includes/es_' . $this->ClassPrefix . '.php','w');
        fwrite($fp, '<?php' . PHP_EOL);
        foreach($modules as $module) {
            fwrite($fp, 'require_once \'common/' . $module . '\';' . PHP_EOL);
        }
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
                    $prop_comments[] = ' * @property ' . (isset($prop_type['properties']) ? 'object' : $prop_type['type']) . ' ' . $name;
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
                $fp = fopen($this->SchemaPath . '/' . $schemaname . '.php', 'w');
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
                $fp = fopen($this->ClassPath . '/' .  $classname . '.php', 'w');
                fwrite($fp, $code);
                fclose($fp);

                $modules[] = $this->ConnectionClassName . '/schema/' . $schemaname . '.php';
                $modules[] = $this->ConnectionClassName . '/' .  $classname . '.php';
            }
        }

        return $modules;
    }
}