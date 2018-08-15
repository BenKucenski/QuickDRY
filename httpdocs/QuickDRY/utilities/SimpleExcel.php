<?php
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

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
        Halt('Deprecated: Use ExportSpreadsheets');
    }

    /**
     * @param string $filename
     * @param SimpleExcel[] $reports
     */
    public static function MultiSheet2007($filename, $reports)
    {
        Halt('Deprecated: Use ExportSpreadsheets');
    }

    /**
     * @param SimpleExcel $report
     */
    public static function SingleSheet(SimpleExcel $report)
    {
        Halt('Deprecated: use ExportSpreadsheet');
    }

    /**
     * @param SimpleExcel $report
     */
    public static function SingleSheet2007(SimpleExcel $report)
    {
        Halt('Deprecated: use ExportSpreadsheet');
    }

    /**
     * @param Spreadsheet $sheet
     */
    private static function SetDefaultSecurity(\PhpOffice\PhpSpreadsheet\Worksheet\Worksheet &$sheet)
    {
        // when marking a sheet protected, there are a number of settings that should not be set by default
        $protection = $sheet->getProtection();
        $protection->setSelectUnlockedCells(false);
        $protection->setSelectLockedCells(false);
        $protection->setFormatCells(true);
        $protection->setFormatColumns(true);
        $protection->setFormatRows(true);
        $protection->setInsertColumns(true);
        $protection->setInsertHyperlinks(true);
        $protection->setInsertRows(true);
        $protection->setDeleteColumns(true);
        $protection->setDeleteRows(true);

    }

    /**
     * @param SimpleExcel $se
     */
    public static function ExportSpreadsheet(SimpleExcel &$se)
    {
        if(!$se->Filename) {
            Halt('Filename required');
        }
        $se->Title = $se->Title ? substr($se->Title, 0, 31) : 'Sheet'; // max 31 characters
        $parts = pathinfo($se->Filename);
        if(!isset($parts['extension']) || strcasecmp($parts['extension'], 'xlsx') !== 0) {
            $se->Filename .= '.xlsx';
        }

        $spreadsheet = new Spreadsheet();
        try {
            $sheet = $spreadsheet->getActiveSheet();
        } catch (Exception $ex) {
            Debug::Halt($ex);
        }
        self::SetDefaultSecurity($sheet);
        $sheet->setTitle($se->Title);
        $sheet_row = 1;
        $sheet_column = 'A';
        foreach ($se->Columns as $column) {
            self::_SetSpreadsheetCellValue($sheet, $sheet_column, $sheet_row, $column->Header, $column->PropertyType);
            $sheet_column++;
        }
        $sheet_row++;
        foreach ($se->Report as $item) {
            $sheet_column = 'A';
            foreach ($se->Columns as $column) {
                if(!is_object($item)) {
                    Halt($item);
                }
                try { // need to use try catch so that magic __get columns are accessible
                    $value = $item->{$column->Property};
                } catch(Exception $ex) {
                    $value = '';
                }

                self::_SetSpreadsheetCellValue($sheet, $sheet_column, $sheet_row, $value, $column->PropertyType);
                $sheet_column++;
            }
            $sheet_row++;
        }


        try {
            $writer = new Xlsx($spreadsheet);
            if (isset($_SERVER['HTTP_HOST'])) {
                header('Content-Type: application/vnd.ms-excel');
                header('Content-Disposition: attachment;filename="' . $se->Filename . '"');
                header('Cache-Control: max-age=0');
                $writer->save('php://output');
                exit;
            } else {
                $writer->save($se->Filename);
            }
        } catch (Exception $ex) {
            Debug::Halt($ex);
        }

    }

    /**
     * @param $filename
     * @param SimpleExcel[] $se
     */
    public static function ExportSpreadsheets($filename, &$ses)
    {
        $spreadsheet = new Spreadsheet();

        $total_sheets = sizeof($ses);

        foreach ($ses as $sheet => $report) {
            if (!isset($_SERVER['HTTP_HOST'])) {
                Log::Insert(($sheet + 1) . ' / ' . $total_sheets . ' : ' . $report->Title, true);
            }
            if ($sheet > 0) {
                try {
                    $spreadsheet->createSheet($sheet);
                } catch (Exception $ex) {
                    Halt($ex);
                }
            }
            try {
                $spreadsheet->setActiveSheetIndex($sheet);
            } catch (Exception $ex) {
                Debug::Halt($ex);
            }
            try {
                $sheet = $spreadsheet->getActiveSheet();
                $sheet->setTitle($report->Title ? $report->Title : 'Sheet ' . ($sheet + 1));
            } catch (Exception $ex) {
                Halt($ex);
            }
            self::SetDefaultSecurity($sheet);

            $sheet_row = 1;

            $sheet_column = 'A';
            foreach ($report->Columns as $column) {
                self::_SetSpreadsheetCellValue($sheet, $sheet_column, $sheet_row, $column->Header, $column->PropertyType);
                $sheet_column++;
            }
            $sheet_row++;
            if ($report->Report && is_array($report->Report)) {
                foreach ($report->Report as $item) {
                    $sheet_column = 'A';
                    foreach ($report->Columns as $column) {
                        if(!is_object($item)) {
                            Halt($item);
                        }
                        try { // need to use try catch so that magic __get columns are accessible
                            $value = $item->{$column->Property};
                        } catch(Exception $ex) {
                            $value = '';
                        }
                        self::_SetSpreadsheetCellValue($sheet, $sheet_column, $sheet_row, $value, $column->PropertyType);
                        $sheet_column++;
                    }
                    $sheet_row++;
                }
            }
        }


        try {
            $writer = new Xlsx($spreadsheet);
            if (isset($_SERVER['HTTP_HOST'])) {
                header('Content-Type: application/vnd.ms-excel');
                header('Content-Disposition: attachment;filename="' . $filename . '"');
                header('Cache-Control: max-age=0');
                $writer->save('php://output');
                exit;
            } else {
                $writer->save($filename);
            }
        } catch (Exception $ex) {
            Debug::Halt($ex);
        }

    }

    /**
     * @param SimpleExcel $se
     */
    public static function ExportCSV(SimpleExcel &$se)
    {
        $spreadsheet = new Spreadsheet();
        try {
            $sheet = $spreadsheet->getActiveSheet();
        } catch (Exception $ex) {
            Debug::Halt($ex);
        }
        $sheet->setTitle($se->Title);
        $sheet_row = 1;
        $sheet_column = 'A';
        foreach ($se->Columns as $column) {
            self::_SetSpreadsheetCellValue($sheet, $sheet_column, $sheet_row, $column->Header, $column->PropertyType);
            $sheet_column++;
        }
        $sheet_row++;
        foreach ($se->Report as $item) {
            $sheet_column = 'A';
            foreach ($se->Columns as $column) {
                self::_SetSpreadsheetCellValue($sheet, $sheet_column, $sheet_row, $item->{$column->Property}, $column->PropertyType);
                $sheet_column++;
            }
            $sheet_row++;
        }


        try {
            $writer = new \PhpOffice\PhpSpreadsheet\Writer\Csv($spreadsheet);
            if (isset($_SERVER['HTTP_HOST'])) {
                header('Content-Type: text/csv');
                header('Content-Disposition: attachment;filename="' . $se->Filename . '"');
                header('Cache-Control: max-age=0');
                $writer->save('php://output');
                exit;
            } else {
                $writer->save($se->Filename);
            }
        } catch (Exception $ex) {
            Debug::Halt($ex);
        }

    }


    /**
     * @param \PhpOffice\PhpSpreadsheet\Worksheet\Worksheet $sheet
     * @param $sheet_column
     * @param $sheet_row
     * @param $value
     * @param string $property_type
     */
    private static function _SetSpreadsheetCellValue(\PhpOffice\PhpSpreadsheet\Worksheet\Worksheet &$sheet, $sheet_column, $sheet_row, $value, $property_type = '')
    {
        if (is_object($value)) {
            if ($value instanceof DateTime) {
                $value = $property_type == SIMPLE_EXCEL_PROPERTY_TYPE_DATE ? Dates::Datestamp($value, '') : Dates::Timestamp($value, '');
            }
        }

        if ($property_type === SIMPLE_EXCEL_PROPERTY_TYPE_AS_GIVEN) {
            try {
                $sheet
                    ->getStyle($sheet_column . $sheet_row)
                    ->getNumberFormat()
                    ->setFormatCode(
                        \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING
                    );
            } catch (Exception $ex) {
                Debug::Halt($ex);
            }

            $sheet->setCellValueExplicit($sheet_column . $sheet_row, $value, \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
        } else {
            if ($property_type == SIMPLE_EXCEL_PROPERTY_TYPE_DATE) {
                try {
                    $sheet
                        ->getStyle($sheet_column . $sheet_row)
                        ->getNumberFormat()
                        ->setFormatCode(
                            \PhpOffice\PhpSpreadsheet\Style\NumberFormat::FORMAT_DATE_YYYYMMDD2
                        );
                } catch (Exception $ex) {
                    Debug::Halt($ex);
                }
            }
            if($property_type == SIMPLE_EXCEL_PROPERTY_TYPE_CURRENCY) {
                try {
                    $sheet
                        ->getStyle($sheet_column . $sheet_row)
                        ->getNumberFormat()
                        ->setFormatCode('#,##0.00');
                } catch (Exception $ex) {
                    Debug::Halt($ex);
                }
            }

            if (is_array($value)) {
                Debug::Halt(['value cannot be an array', $value]);
            }
            try {
                $sheet->setCellValue($sheet_column . $sheet_row, $value);
            } catch (Exception $ex) {
                Halt($ex);
            }
        }
    }
}