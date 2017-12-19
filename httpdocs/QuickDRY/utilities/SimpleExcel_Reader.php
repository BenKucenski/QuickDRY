<?php

class SimpleExcel_Reader extends SafeClass
{
    public static function FromFilename($file, $process_cells = true, $debug = false, $row_limit = null)
    {
        if ($debug) {
            Log::Insert('SimpleExcel_Reader::FromFilename', true);
        }
        try {
            if ($debug) {
                Log::Insert('Getting file type', true);
            }
            $inputFileType = PHPExcel_IOFactory::identify($file);
            if ($debug) {
                Log::Insert('File type: ' . $inputFileType, true);
            }
            if ($debug) {
                Log::Insert('Creating reader', true);
            }
            $objReader = PHPExcel_IOFactory::createReader($inputFileType);
            //$objReader->setReadDataOnly(true); // this isn't a function for CSV files

            if ($debug) {
                Log::Insert('Loading File', true);
            }
            $objPHPExcel = $objReader->load($file);
            return self::ToReport($objPHPExcel, $process_cells, $debug, $row_limit);
        } catch (Exception $e) {
            die('Error loading file "' . $file . '": ' . $e->getMessage());
        }
    }

    public static function ToReport(PHPExcel &$objPHPExcel, $process_cells = true, $debug = false, $row_limit = null)
    {
        if ($debug) {
            Log::Insert('SimpleExcel_Reader::ToReport', true);
        }
        $report = [];
        $sheetCount = $objPHPExcel->getSheetCount();
        $sheetNames = $objPHPExcel->getSheetNames();

        if ($debug) {
            Log::Insert('Sheet Count: ' . $sheetCount, true);
        }

        for ($sheet = 0; $sheet < $sheetCount; $sheet++) {
            try {
                if ($debug) {
                    Log::Insert('Sheet: ' . $sheetNames[$sheet], true);
                }

                $activeSheet = $objPHPExcel->setActiveSheetIndex($sheet);

                $report[$sheetNames[$sheet]] = [];

                $rows = $activeSheet->getHighestRow();
                $cols = $activeSheet->getHighestColumn();

                if ($debug) {
                    Log::Insert('Rows: ' . $rows . ', Cols: ' . $cols, true);
                }

                $per_page = 100;
                for ($row = 1; $row <= $rows; $row += $per_page) {
                    if ($debug) {
                        Log::Insert($row . ' / ' . $rows, true);
                    }
                    $end = ($row + $per_page);
                    if ($end > $rows) {
                        $end = $rows;
                    }
                    if($row_limit && $row > $row_limit) {
                        break;
                    }
                    $report[$sheetNames[$sheet]] += $activeSheet->rangeToArray('A' . $row . ':' . $cols . ($end),
                        NULL,
                        $process_cells,
                        $process_cells);

                }

                if ($debug) {
                    Log::Insert('Done Reading Data', true);
                }

            } catch (Exception $ex) {
                Halt($ex);
            }
        }
        return $report;
    }
}