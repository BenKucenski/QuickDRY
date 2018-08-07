<?php
use PhpOffice\PhpWord\PhpWord;

class SimpleWordDoc extends SafeClass
{
    public static function RenderHTML($html, $filename)
    {
        if(!defined('WORD_TEMP_DIR')) {
            Halt('WORD_TEMP_DIR must be defined');
        }
        PhpOffice\PhpWord\Settings::setTempDir(WORD_TEMP_DIR);

        $phpWord = new PhpWord();
        $section = $phpWord->addSection();
        PhpOffice\PhpWord\Shared\Html::addHtml($section, $html, true, false);

        try {
            $objWriter = \PhpOffice\PhpWord\IOFactory::createWriter($phpWord, 'Word2007');
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