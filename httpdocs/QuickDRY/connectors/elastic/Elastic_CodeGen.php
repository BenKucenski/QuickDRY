<?php
namespace QuickDRY\Connectors;

use QuickDRY\Utilities\Log;
use QuickDRY\Utilities\SafeClass;

class Elastic_CodeGen extends SafeClass
{
  public ?string $DestinationFolder;
  public ?string $ConnectionClassName;
  public ?string $ClassPrefix;
  public array $Indexes;
  public array $ExcludeStartsWith;

  public string $ClassFolder;
  public string $ClassSchemaFolder;

  public function __construct(?string $ConnectionClassName, ?string $ClassPrefix, array $ExcludeStartsWith = [], string $DestinationFolder = '../httpdocs')
  {
    if (!class_exists($ConnectionClassName)) {
      CleanHalt(['$ConnectionClassName ' . $ConnectionClassName . ' does not exist']);
    }

    if (!method_exists($ConnectionClassName, 'GetIndexes')) {
      CleanHalt(['$ConnectionClassName ' . $ConnectionClassName . ' does not implement GetIndexes']);
    }

    $this->DestinationFolder = $DestinationFolder;
    $this->ConnectionClassName = $ConnectionClassName;
    $this->ClassPrefix = $ClassPrefix;
    $this->ExcludeStartsWith = $ExcludeStartsWith;

    $this->ClassFolder = $this->DestinationFolder . '/common/' . $ConnectionClassName;
    $this->ClassSchemaFolder = $this->ClassFolder . '/schema';

    if (!is_dir($this->ClassFolder)) {
      mkdir($this->ClassFolder);
    }

    if (!is_dir($this->ClassSchemaFolder)) {
      mkdir($this->ClassSchemaFolder);
    }


    $this->GetIndexes();
    $modules = $this->GenerateCode();

    $mod_map = [];
    foreach ($modules as $mod => $file) {
      $mod_map[] = '\'' . $mod . '\' => \'' . $file . '\',';
    }

    $template = file_get_contents(__DIR__ . '/../_templates/es_autoloader.txt');
    $vars = [
      'ClassPrefix' => strtolower($this->ClassPrefix),
      'mod_map' => implode("\r\n\t\t", $mod_map),
    ];

    $include_php = $template;
    foreach ($vars as $name => $v) {
      $include_php = str_replace('[[' . $name . ']]', $v, $include_php);
    }

    $fp = fopen($this->DestinationFolder . '/includes/es_' . $this->ClassPrefix . '.php', 'w');
    fwrite($fp, $include_php);
    fclose($fp);
  }

  private function GetIndexes()
  {
    $connection = $this->ConnectionClassName;
    if(!method_exists($connection, 'GetIndexes')) {
      exit($connection .'::GetIndexes');
    }
    $this->Indexes = $connection::GetIndexes();
    if (!is_array($this->ExcludeStartsWith) || !sizeof($this->ExcludeStartsWith)) {
      return;
    }

    foreach ($this->Indexes as $index => $settings) {
      foreach ($this->ExcludeStartsWith as $item) {
        $item = trim($item);
        if (!$item) {
          continue;
        }
        if (strcasecmp(substr($index, 0, strlen($item)), $item) == 0) {
          Log::Insert([$index, $item], true);
          unset($this->Indexes[$index]);
        }
      }
    }
  }

  private function GenerateCode(): array
  {
    $modules = [];

    foreach ($this->Indexes as $index => $settings) {
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
          $alias = preg_replace('/[^a-z0-9]/si', '_', $name);
          $prop_comments[] = ' * @property ' . (isset($prop_type['properties']) ? 'object' : SQLCodeGen::ColumnTypeToProperty(preg_replace('/\(.*?\)/si', '', $prop_type['type']))) . ' ' . $name;
          $prop_code[] = '    public $' . $alias . ';';

          if ($alias !== $name) {
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

        if (sizeof($alias_list)) {
          $to_array_code = '
    public function ToArray($ignore_empty = false, $exclude = [])
    {
        $temp = parent::ToArray($ignore_empty, $exclude);
                    ';
          foreach ($alias_list as $name => $alias) {
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

        $template = file_get_contents(__DIR__ . '/../_templates/es_class_schema.txt');
        $vars = [
          'schemaname' => $schemaname,
          'prop_comments' => implode(PHP_EOL, $prop_comments),
          'ConnectionClassName' => $this->ConnectionClassName,
          'index' => $index,
          'type' => $type,
          'prop_code' => implode(PHP_EOL, $prop_code),
          'alias_get_code' => $alias_get_code,
          'alias_set_code' => $alias_set_code,
          'to_array_code' => $to_array_code,
        ];

        $include_php = $template;
        foreach ($vars as $name => $v) {
          $include_php = str_replace('[[' . $name . ']]', $v, $include_php);
        }

        $fp = fopen($this->ClassSchemaFolder . '/' . $schemaname . '.php', 'w');
        fwrite($fp, $include_php);
        fclose($fp);

        $template = file_get_contents(__DIR__ . '/../_templates/es_class.txt');
        $vars = [
          'schemaname' => $schemaname,
          'classname' => $classname,
        ];

        $include_php = $template;
        foreach ($vars as $name => $v) {
          $include_php = str_replace('[[' . $name . ']]', $v, $include_php);
        }

        if (!file_exists($this->ClassFolder . '/' . $classname . '.php')) { // don't overwrite this file
          $fp = fopen($this->ClassFolder . '/' . $classname . '.php', 'w');
          fwrite($fp, $include_php);
          fclose($fp);
        }

        $modules[$schemaname] = 'common/' . $this->ConnectionClassName . '/schema/' . $schemaname . '.php';
        $modules[$classname] = 'common/' . $this->ConnectionClassName . '/' . $classname . '.php';
      }
    }

    return $modules;
  }
}