<?php
use PhpOffice\PhpWord\PhpWord;

class SimpleWordDoc extends SafeClass
{
    public static function RenderHTML($html, $filename)
    {
        $phpWord = new PhpWord();
        $section = $phpWord->addSection();
        PhpOffice\PhpWord\Shared\Html::addHtml($section, $html, true, false);

        try {
            if (isset($_SERVER['HTTP_HOST'])) {
                header('Content-Type: application/vnd.ms-excel');
                header('Content-Disposition: attachment;filename="' . $filename . '"');
                header('Cache-Control: max-age=0');
                $phpWord->save('php://output');
                exit;
            } else {
                $phpWord->save($filename);
            }
        } catch (Exception $ex) {
            Debug::Halt($ex);
        }
    }
}