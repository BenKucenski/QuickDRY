<?php

namespace QuickDRYInstance\Common;

use QuickDRY\Utilities\SafeClass;

/**
 * Class FileClass
 */
class FileClass extends SafeClass
{
  public ?int $user_id = null;
  public ?string $file_name = null;
  public ?string $file_type = null;
  public ?string $file_hash = null;
  public ?int $file_size = null;
  public ?string $file_ext = null;
  public ?string $created_at = null;

  public ?int $entity_id = null;
  public ?string $entity_type = null;

  /**
   * @param string $name
   * @return null|string
   */
  public function __get(string $name)
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

  public function Save()
  {
    // TODO: add database table to store these records into
  }
}