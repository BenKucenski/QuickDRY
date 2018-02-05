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
            try {
                $objPHPExcel->setActiveSheetIndex($sheet);
            } catch (Exception $ex) {
                Debug::Halt($ex);
            }
            $objPHPExcel->getActiveSheet()->setTitle($report->Title);
            $sheet_row = 1;


            $sheet_column = 'A';
            foreach ($report->Columns as $column) {
                self::_SetCellValue($objPHPExcel, $sheet_column, $sheet_row, $column->Header, $column->PropertyType);
                $sheet_column++;
            }
            $sheet_row++;
            if ($report->Report && is_array($report->Report)) {
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

        try {
            $objWriter = \PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel5');
            if(isset($_SERVER['HTTP_HOST'])) {
                header('Content-Type: application/vnd.ms-excel');
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

    /**
     * @param string $filename
     * @param SimpleExcel[] $reports
     */
    public static function MultiSheet2007($filename, $reports)
    {
        $objPHPExcel = new \PHPExcel();

        $total_sheets = sizeof($reports);

        foreach ($reports as $sheet => $report) {
            if(!isset($_SERVER['HTTP_HOST'])) {
                Log::Insert(($sheet + 1) . ' / ' . $total_sheets . ' : '. $report->Title, true);
            }
            if ($sheet > 0) {
                try {
                    $objPHPExcel->createSheet($sheet);
                } catch(Exception $ex) {
                    Halt($ex);
                }
            }
            try {
                $objPHPExcel->setActiveSheetIndex($sheet);
            } catch (Exception $ex) {
                Debug::Halt($ex);
            }
            try {
                $objPHPExcel->getActiveSheet()->setTitle($report->Title);
            } catch(Exception $ex) {
                Halt($ex);
            }
            $sheet_row = 1;


            $sheet_column = 'A';
            foreach ($report->Columns as $column) {
                self::_SetCellValue($objPHPExcel, $sheet_column, $sheet_row, $column->Header, $column->PropertyType);
                $sheet_column++;
            }
            $sheet_row++;
            if ($report->Report && is_array($report->Report)) {
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

        try {
            $objWriter = \PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel2007');
            if(isset($_SERVER['HTTP_HOST'])) {
                header('Content-Type: application/vnd.ms-excel');
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

    /**
     * @param SimpleExcel $report
     */
    public static function SingleSheet(SimpleExcel $report)
    {
        $objPHPExcel = new \PHPExcel();

        try {
            $objPHPExcel->setActiveSheetIndex(0);
        } catch (Exception $ex) {
            Debug::Halt($ex);
        }
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


        try {
            $objWriter = \PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel5');
            if(isset($_SERVER['HTTP_HOST'])) {
                header('Content-Type: application/vnd.ms-excel');
                header('Content-Disposition: attachment;filename="' . $report->Filename . '"');
                header('Cache-Control: max-age=0');
                $objWriter->save('php://output');
                exit;
            } else {
                $objWriter->save($report->Filename);
            }
        } catch (Exception $ex) {
            Debug::Halt($ex);
        }
    }

    /**
     * @param SimpleExcel $report
     */
    public static function SingleSheet2007(SimpleExcel $report)
    {
        $objPHPExcel = new \PHPExcel();

        try {
            $objPHPExcel->setActiveSheetIndex(0);
        } catch (Exception $ex) {
            Debug::Halt($ex);
        }

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


        try {
            $objWriter = \PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel2007');
            if(isset($_SERVER['HTTP_HOST'])) {
                header('Content-Type: application/vnd.ms-excel');
                header('Content-Disposition: attachment;filename="' . $report->Filename . '"');
                header('Cache-Control: max-age=0');
                $objWriter->save('php://output');
                exit;
            } else {
                $objWriter->save($report->Filename);
            }
        } catch (Exception $ex) {
            Debug::Halt($ex);
        }
    }

    private static function _SetCellValue(\PHPExcel &$objPHPExcel, $sheet_column, $sheet_row, $value, $property_type = '')
    {
        if (is_object($value)) {
            if ($value instanceof DateTime) {
                $value = $property_type == SIMPLE_EXCEL_PROPERTY_TYPE_DATE ? Dates::Datestamp($value, '') : Dates::Timestamp($value, '');
            }
        }

        $sheet = $objPHPExcel->getActiveSheet();

        if ($property_type === SIMPLE_EXCEL_PROPERTY_TYPE_AS_GIVEN) {
            try {
                $sheet
                    ->getStyle($sheet_column . $sheet_row)
                    ->getNumberFormat()
                    ->setFormatCode(
                        \PHPExcel_Style_NumberFormat::FORMAT_TEXT
                    );
            } catch (Exception $ex) {
                Debug::Halt($ex);
            }

            $sheet->setCellValueExplicit($sheet_column . $sheet_row, $value, \PHPExcel_Cell_DataType::TYPE_STRING);
        } else {
            if ($property_type == SIMPLE_EXCEL_PROPERTY_TYPE_DATE) {
                try {
                    $sheet
                        ->getStyle($sheet_column . $sheet_row)
                        ->getNumberFormat()
                        ->setFormatCode(
                            \PHPExcel_Style_NumberFormat::FORMAT_DATE_YYYYMMDD2
                        );
                } catch (Exception $ex) {
                    Debug::Halt($ex);
                }
            }

            if (is_array($value)) {
                Debug::Halt(['value cannot be an array', $value]);
            }
            $sheet->setCellValue($sheet_column . $sheet_row, $value);
        }
    }
}