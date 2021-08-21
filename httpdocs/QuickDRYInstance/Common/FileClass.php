<?php

namespace QuickDRYInstance\Common;


use QuickDRY\Utilities\SafeClass;

/**
 * Class FileClass
 *
 * @property string server_location
 */
class FileClass extends SafeClass
{
  public int $user_id;
  public string $file_name;
  public string $file_type;
  public string $file_hash;
  public int $file_size;
  public string $file_ext;
  public string $created_at;

  public int $entity_id;
  public string $entity_type;

  /**
   * @param $name
   * @return null|string
   */
  public function __get($name)
  {
    switch ($name) {
      case 'server_location':
        return self::UploadFolder($this->file_hash, $this->file_ext);
    }
    return parent::__get($name);
  }

  /**
   * @param $hash
   * @param string $ext
   * @return string
   */
  public static function UploadFolder($hash, string $ext = 'tmp'): string
  {
    if (!defined('UPLOAD_DIR')) {
      Halt('QuickDRY Error: UPLOAD_DIR is undefined');
      exit;
    }
    $dir = UPLOAD_DIR;
    for ($j = 0; $j < 2; $j++) {
      $dir .= $hash[$j] . '/';
      if (!is_dir($dir))
        mkdir($dir, 0777, true);
    }
    return $dir . $hash . '.' . $ext;
  }

  public function Save(): int
  {
    return 0;
  }
}