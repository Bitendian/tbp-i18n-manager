<?php

namespace Bitendian\TBP\I18n\Modules\Gettext\ExportToXLS;

use Bitendian\TBP\UI\AbstractWidget;

use Bitendian\TBP\Utils\SystemMessages;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;

abstract class AbstractExportExcelWidget extends AbstractWidget
{
    public $title;
    public $headers;
    public $body;
    public $creator = 'CIRPROTEC';

    public function render()
    {
        try {
            // Maximum 31 characters allowed in sheet title
            $this->title = substr($this->title, 0, min(strlen($this->title), 30));

            /* Create PHPExcel object and set basic properties */
            $objPhpExcel = new Spreadsheet();
            $objPhpExcel->getProperties()
                ->setCreator($this->creator)
                ->setTitle($this->title);

            /* Set headers */
            for ($i = 1; $i <= count($this->headers); $i++) {
                $objPhpExcel->setActiveSheetIndex(0)
                    ->setCellValue(Coordinate::stringFromColumnIndex($i) . '1', $this->headers[$i-1]);
            }

            /* Set body */
            $i = 2;
            foreach ($this->body as &$row) {
                $j = 1;
                foreach ($row as &$element) {
                    $objPhpExcel->setActiveSheetIndex(0)
                        ->setCellValue(Coordinate::stringFromColumnIndex($j) . $i, $element);
                    $j++;
                }
                $i++;
            }

            /* Set headers style */
            $headersStyle = array(
                'font' => array(
                    'name'  => 'Arial',
                    'bold'  => true,
                    'color' => array(
                        'rgb' => 'FFFFFF'
                    )
                ),
                'fill' => array(
                    'fillType'       => Fill::FILL_SOLID,
                    'color' => array(
                        'rgb' => '009641'
                    )
                ),
                'alignment' =>  array(
                    'horizontal'=> Alignment::HORIZONTAL_CENTER,
                    'vertical'  => Alignment::VERTICAL_TOP,
                    'wrap'      => TRUE
                )
            );
            $objPhpExcel->getActiveSheet()->getStyle('A1:'. Coordinate::stringFromColumnIndex(count($this->headers)).'1')->applyFromArray($headersStyle);

            /* Set body style */
            $bodyStyle = array(
                'font' => array(
                    'name'  => 'Arial',
                    'color' => array(
                        'rgb' => '000000'
                    )
                ),
                'borders' => array(
                    'left' => array(
                        'style' => Border::BORDER_THIN ,
                        'color' => array(
                            'rgb' => '3a2a47'
                        )
                    )
                )
            );
            $objPhpExcel->getActiveSheet()->getStyle('A2:'.Coordinate::stringFromColumnIndex(count($this->headers)).($i-1))->applyFromArray($bodyStyle);

            /* Set auto-width */
            for ($i = 1; $i <= count($this->headers); $i++) {
                $objPhpExcel->setActiveSheetIndex(0)->getColumnDimension(Coordinate::stringFromColumnIndex($i))->setAutoSize(true);
            }

            /* Set sheet name */
            $objPhpExcel->getActiveSheet()->setTitle($this->title);

            /* Set sheet as default */
            $objPhpExcel->setActiveSheetIndex(0);

            /* Lock headers */
            $objPhpExcel->getActiveSheet()->freezePaneByColumnAndRow(0, 2);

            /* File is sent to web browser, with $this->title as name, in format Excel 2007 */
            header('Content-Type: application/vnd.ms-excel');
            header('Content-Disposition: attachment;filename="' . $this->title . '.xlsx"');
            header('Cache-Control: max-age=0');

            $objWriter = IOFactory::createWriter($objPhpExcel, 'Xlsx');
            $objWriter->save('php://output');
            die();
        } catch (\Exception $exception) {
            SystemMessages::addError($exception->getMessage());
            SystemMessages::save();
        }
    }
}