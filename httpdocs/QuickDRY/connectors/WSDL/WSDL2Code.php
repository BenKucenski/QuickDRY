<?php

use QuickDRY\Utilities\Log;
use QuickDRY\Utilities\SafeClass;
use QuickDRY\Utilities\Strings;

/**
 * Class WSDL2Code
 *
 * @property string TargetNamespace
 * @property WSDLFunction[] Functions
 */
class WSDL2Code extends SafeClass
{
  public $TargetNamespace;
  public $Functions;
  public $BaseDir;

  public function Generate(string $URL, string $ClassName, string $BaseDir = '../httpdocs')
  {
    $this->BaseDir = $BaseDir;

    $this->GetFunctions($URL);

    $this->GenerateCode($ClassName);
  }

  private function GenerateCode($ClassName)
  {
    $function_code = [];

    usort($this->Functions, function (WSDLFunction $a, WSDLFunction $b) {
      return strcasecmp($a->Name, $b->Name);
    });

    foreach ($this->Functions as $function) {
      $result_array = [];
      $result_array[] = '\'xml\' => $a';
      if ($function->Result) {
        foreach ($function->Result->Parameters as $param) {
          $key_a = strtoupper($function->Result->Name);
          $key_b = strtoupper($param->Name);

          $result_array[] = '\'' . $param->Name . '\' => isset($a[\'' . $key_a . '\'][\'' . $key_b . '\']) ? $a[\'' . $key_a . '\'][\'' . $key_b . '\'] : null';
        }
      }
      $parameters = [];
      $param_comment = [];
      $param_res = [];
      if ($function->Parameters) {
        foreach ($function->Parameters as $param) {
          $parameters[] = '$' . $param->Name;
          $param_comment[] = '     * @param $' . $param->Name;
          $param_res[] = '        $res->' . $param->Name . ' = $' . $param->Name . ';';
        }
      }

      $code = '
    /**
' . implode("\r\n", $param_comment) . '
     */
    protected static function _' . $function->Name . '(' . implode(', ', $parameters) . ')
    {
        $res = new self();
        $res->path = static::$BASE_URL . \'/' . $function->Name . '\';
' . implode("\r\n", $param_res) . '

        $res->Post();
        $xml = $res->raw;
        $a = Strings::SimpleXMLToArray($xml);
        return [
            ' . implode(",\r\n            ", $result_array) . '
        ];
    }';
      $function_code[] = $code;
    }

    $code = '<?php
/**
 * Class ' . $ClassName . 'Base
 */
class ' . $ClassName . 'Base extends APIRequest
{
' . implode("\r\n", $function_code) . '
}
        ';

    $dir = $this->BaseDir . '/common/WSDL';
    if (!is_dir($dir)) {
      Log::Insert($dir, true);
      mkdir($dir);
    }

    $filename = $this->BaseDir . '/common/WSDL/' . $ClassName . 'Base.php';
    $fp = fopen($filename, 'w');
    fwrite($fp, $code);
    fclose($fp);
  }

  private function GetFunctions($URL)
  {
    $xml = file_get_contents($URL);

    $services = Strings::SimpleXMLToArray($xml);

    $this->TargetNamespace = $services['WSDL:DEFINITIONS']['WSDL:TYPES']['S:SCHEMA']['TARGETNAMESPACE'];
    $this->Functions = [];

    foreach ($services['WSDL:DEFINITIONS']['WSDL:TYPES']['S:SCHEMA']['S:ELEMENT'] as $i => $function) {

      if (isset($function['NAME'])) {

        if (!isset($function['S:COMPLEXTYPE'][$i]['S:SEQUENCE'][$i]['S:ELEMENT'])) {
          continue;
        }

        $function_name = $function['NAME'];

        $is_response = false;
        if (Strings::EndsWith($function_name, 'Response')) {
          $function_name = Strings::RemoveFromEnd('Response', $function_name);
          $is_response = true;
        }

        $f = !isset($this->Functions[$function_name]) ? new WSDLFunction() : $this->Functions[$function_name];
        $f->Name = $function_name;


        foreach ($function['S:COMPLEXTYPE'][$i]['S:SEQUENCE'][$i]['S:ELEMENT'] as $param) {
          $p = new WSDLParameter();
          $p->MaxOccurs = $param['MAXOCCURS'];
          $p->MinOccurs = $param['MINOCCURS'];
          $p->Name = $param['NAME'];
          $p->Type = $param['TYPE'];
          if ($is_response) {

            $f->AddResponse($p, $function['NAME']);
          } else {
            $f->AddParameter($p);
          }
        }
        $this->Functions[$function_name] = $f;
      }
    }

    foreach ($services['WSDL:DEFINITIONS']['WSDL:TYPES']['S:SCHEMA']['S:COMPLEXTYPE'] as $i => $function) {

      if (!isset($function['NAME'])) {
        continue;
      }

      $function_name = $function['NAME'];

      if (Strings::EndsWith($function_name, 'Result')) {
        $function_name = Strings::RemoveFromEnd('Result', $function_name);
      } else {
        continue;
      }

      $f = !isset($this->Functions[$function_name]) ? new WSDLFunction() : $this->Functions[$function_name];
      $f->Name = $function_name;

      foreach ($function['S:SEQUENCE'][$i]['S:ELEMENT'] as $param) {
        $p = new WSDLParameter();
        $p->MaxOccurs = $param['MAXOCCURS'];
        $p->MinOccurs = $param['MINOCCURS'];
        $p->Name = $param['NAME'];
        $p->Type = $param['TYPE'];
        $f->AddResult($p, $function['NAME']);
      }

      $this->Functions[$function_name] = $f;
    }
  }
}



