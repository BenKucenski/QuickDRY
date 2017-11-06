<?php

/**
 * Class SimpleExcel
 *
 * @property string Filename
 * @property string Title
 * @property stdClass Report
 * @property SimpleExcel_Column[] Columns
 */
class SimpleExcel extends SafeClass
{
    public $Filename;
    public $Report;
    public $Columns;
    public $Title;

    /**
     * @param string $filename
     * @param SimpleExcel[] $reports
     */
    public static function MultiSheet($filename, $reports)
    {
        $objPHPExcel = new \PHPExcel();

        foreach ($reports as $sheet => $report) {
            if ($sheet > 0) {
                $objPHPExcel->createSheet($sheet);
            }
            $objPHPExcel->setActiveSheetIndex($sheet);
            $objPHPExcel->getActiveSheet()->setTitle($report->Title);
            $sheet_row = 1;


            $sheet_column = 'A';
            foreach ($report->Columns as $column) {
                self::_SetCellValue($objPHPExcel, $sheet_column, $sheet_row, $column->Header, $column->PropertyType);
                $sheet_column++;
            }
            $sheet_row++;
            if($report->Report && is_array($report->Report)) {
                foreach ($report->Report as $item) {
                    $sheet_column = 'A';
                    foreach ($report->Columns as $column) {
                        self::_SetCellValue($objPHPExcel, $sheet_column, $sheet_row, $item->{$column->Property}, $column->PropertyType);
                        $sheet_column++;
                    }
                    $sheet_row++;
                }
            }
        }

        header('Content-Type: application/vnd.ms-excel');
        header('Content-Disposition: attachment;filename="' . $filename . '"');
        header('Cache-Control: max-age=0');
        $objWriter = \PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel5');
        $objWriter->save('php://output');
        exit;

    }

    /**
     * @param string $filename
     * @param SimpleExcel[] $reports
     */
    public static function MultiSheet2007($filename, $reports)
    {
        $objPHPExcel = new \PHPExcel();

        foreach ($reports as $sheet => $report) {
            if ($sheet > 0) {
                $objPHPExcel->createSheet($sheet);
            }
            $objPHPExcel->setActiveSheetIndex($sheet);
            $objPHPExcel->getActiveSheet()->setTitle($report->Title);
            $sheet_row = 1;


            $sheet_column = 'A';
            foreach ($report->Columns as $column) {
                self::_SetCellValue($objPHPExcel, $sheet_column, $sheet_row, $column->Header, $column->PropertyType);
                $sheet_column++;
            }
            $sheet_row++;
            if($report->Report && is_array($report->Report)) {
                foreach ($report->Report as $item) {
                    $sheet_column = 'A';
                    foreach ($report->Columns as $column) {
                        self::_SetCellValue($objPHPExcel, $sheet_column, $sheet_row, $item->{$column->Property}, $column->PropertyType);
                        $sheet_column++;
                    }
                    $sheet_row++;
                }
            }
        }

        header('Content-Type: application/vnd.ms-excel');
        header('Content-Disposition: attachment;filename="' . $filename . '"');
        header('Cache-Control: max-age=0');
        $objWriter = \PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel2007');
        $objWriter->save('php://output');
        exit;

    }

    /**
     * @param SimpleExcel $report
     */
    public static function SingleSheet(SimpleExcel $report)
    {
        $objPHPExcel = new \PHPExcel();

        $objPHPExcel->setActiveSheetIndex(0);
        $objPHPExcel->getActiveSheet()->setTitle($report->Title);
        $sheet_row = 1;


        $sheet_column = 'A';
        foreach ($report->Columns as $column) {
            self::_SetCellValue($objPHPExcel, $sheet_column, $sheet_row, $column->Header, $column->PropertyType);
            $sheet_column++;
        }
        $sheet_row++;
        foreach ($report->Report as $item) {
            $sheet_column = 'A';
            foreach ($report->Columns as $column) {
                self::_SetCellValue($objPHPExcel, $sheet_column, $sheet_row, $item->{$column->Property}, $column->PropertyType);
                $sheet_column++;
            }
            $sheet_row++;
        }


        header('Content-Type: application/vnd.ms-excel');
        header('Content-Disposition: attachment;filename="' . $report->Filename . '"');
        header('Cache-Control: max-age=0');
        $objWriter = \PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel5');
        $objWriter->save('php://output');
        exit;
    }

    /**
     * @param SimpleExcel $report
     */
    public static function SingleSheet2007(SimpleExcel $report)
    {
        $objPHPExcel = new \PHPExcel();

        $objPHPExcel->setActiveSheetIndex(0);
        $objPHPExcel->getActiveSheet()->setTitle($report->Title);
        $sheet_row = 1;


        $sheet_column = 'A';
        foreach ($report->Columns as $column) {
            self::_SetCellValue($objPHPExcel, $sheet_column, $sheet_row, $column->Header, $column->PropertyType);
            $sheet_column++;
        }
        $sheet_row++;
        foreach ($report->Report as $item) {
            $sheet_column = 'A';
            foreach ($report->Columns as $column) {
                self::_SetCellValue($objPHPExcel, $sheet_column, $sheet_row, $item->{$column->Property}, $column->PropertyType);
                $sheet_column++;
            }
            $sheet_row++;
        }


        header('Content-Type: application/vnd.ms-excel');
        header('Content-Disposition: attachment;filename="' . $report->Filename . '"');
        header('Cache-Control: max-age=0');
        $objWriter = \PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel2007');
        $objWriter->save('php://output');
        exit;
    }

    private static function _SetCellValue(\PHPExcel &$objPHPExcel, $sheet_column, $sheet_row, $value, $property_type = '')
    {
        if(is_object($value)) {
            if ($value instanceof DateTime) {
                $value = $property_type == SIMPLE_EXCEL_PROPERTY_TYPE_DATE ? Datestamp($value, '') : Timestamp($value, '');
            }
        }

        if ($property_type === SIMPLE_EXCEL_PROPERTY_TYPE_AS_GIVEN) {
            $objPHPExcel->getActiveSheet()
                ->getStyle($sheet_column . $sheet_row)
                ->getNumberFormat()
                ->setFormatCode(
                    \PHPExcel_Style_NumberFormat::FORMAT_TEXT
                );
            $objPHPExcel->getActiveSheet()->setCellValueExplicit($sheet_column . $sheet_row, $value, \PHPExcel_Cell_DataType::TYPE_STRING);
        } else {
            if ($property_type == SIMPLE_EXCEL_PROPERTY_TYPE_DATE) {
                $objPHPExcel->getActiveSheet()
                    ->getStyle($sheet_column . $sheet_row)
                    ->getNumberFormat()
                    ->setFormatCode(
                        \PHPExcel_Style_NumberFormat::FORMAT_DATE_YYYYMMDD2
                    );
            }

            if(is_array($value)) {
                Halt(['value cannot be an array', $value]);
            }
            $objPHPExcel->getActiveSheet()->setCellValue($sheet_column . $sheet_row, $value);
        }
    }
}