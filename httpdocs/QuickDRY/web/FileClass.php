<?php

/**
 * Class FileClass
 */
class FileClass extends SafeClass
{
    public $user_id;
    public $file_name;
    public $file_type;
    public $file_hash;
    public $file_size;
    public $file_ext;
    public $created_at;

    public $entity_id;
    public $entity_type;

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
    public static function UploadFolder($hash, $ext = 'tmp')
    {
        if(!defined('UPLOAD_DIR')) {
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