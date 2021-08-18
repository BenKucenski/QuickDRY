<?php
namespace QuickDRY\Utilities;


use Exception;
use PhpOffice\PhpWord\IOFactory;
use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\Settings;
use PhpOffice\PhpWord\Shared\Html;

class SimpleWordDoc extends SafeClass
{
    public static function RenderHTML($html, $filename)
    {
        if(!defined('WORD_TEMP_DIR')) {
            exit('QuickDRY Error: WORD_TEMP_DIR must be defined');
        }
        Settings::setTempDir(WORD_TEMP_DIR);

        $phpWord = new PhpWord();
        $section = $phpWord->addSection();
        Html::addHtml($section, $html, true, false);

        try {
            $objWriter = IOFactory::createWriter($phpWord);
            if (isset($_SERVER['HTTP_HOST'])) {
                header('Content-Type: application/vnd.ms-word');
                header('Content-Disposition: attachment;filename="' . $filename . '"');
                header('Cache-Control: max-age=0');
                $objWriter->save('php://output');
                exit;
            } else {
                $objWriter->save($filename);
            }
        } catch (Exception $ex) {
            Debug::Halt($ex);
        }
    }
}