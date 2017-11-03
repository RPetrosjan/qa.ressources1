<?php


namespace Act\ResourceBundle\Services\Export;

use Act\ResourceBundle\Services\Resource\ResourcesUsageManager;
use Symfony\Component\Translation\TranslatorInterface;
use Liuggio\ExcelBundle\Factory;
use Symfony\Component\HttpFoundation\Response;

class ResourceUsageExport
{
    private $excel;
    private $translator;
    private $rum;

    public function __construct(Factory $excel, TranslatorInterface $translator, ResourcesUsageManager $rum)
    {
        $this->excel = $excel;
        $this->translator = $translator;
        $this->rum = $rum;
    }

    public function exportExcel(\DateTime $start, \DateTime $end, array $resources, array $tags = null, array $projects = null)
    {
        $filename = $this->translator->trans('resources.usage').' - '.$start->format('d/m/Y').' - '.$end->format('d/m/Y');
        $data     = $this->rum->generateUsageData($resources, $start, $end, $tags, $projects);

        $dataSeriesLabels = array();
        $dataSeriesValues = array();
        $xAxisTickValues  = array();

        $objPHPExcel = $this->excel->createPHPExcelObject();
        $objPHPExcel
            ->getProperties()
            ->setCompany("Actency")
            ->setCreator("Act&Ressources")
            ->setTitle($filename);
        $objWriter = $this->excel->createWriter($objPHPExcel, 'Excel2007');
        $sheet = $objPHPExcel->getActiveSheet();

        // First line is the document title
        $range = 'A1:' . \PHPExcel_Cell::stringFromColumnIndex(count($data['charge'])) . '1';
        $sheet->setCellValueByColumnAndRow(0, 1, $filename);
        $sheet->mergeCells($range);
        $style = array(
          'fill' => array(
            'type' => \PHPExcel_Style_Fill::FILL_SOLID,
            'color' => array('rgb' => '000000'),
          ),
          'borders' => array(
            'allborders' => array(
              'style' => \PHPExcel_Style_Border::BORDER_THIN,
              'color' => array('rgb' => '000000'),
            )
          ),
          'font' => array(
            'bold' => true,
            'color' => array('rgb' => 'FFFFFF'),
          )
        );
        $sheet->getStyle($range)->applyFromArray($style);

        // Second row contains the dates
        $row = 2; $col = 1;
        foreach ($data['charge'] as $week => $weekData) {
            $sheet->setCellValueByColumnAndRow($col, $row, 'W'.$week);
            $col++;
        }
        $xAxisTickValues[] = new \PHPExcel_Chart_DataSeriesValues('String', 'Worksheet!$B$' . $row . ':$' . \PHPExcel_Cell::stringFromColumnIndex(count($data['charge'])) . '$' . $row, null, 3);

        // Each row is each team resource charges, followed by the team total
        $row = 3; $col = 0;
        foreach ($data['team'] as $id => $teamData) {
            // Print each resource charges
            foreach ($teamData['resources'] as $resource) {
                $sheet->setCellValueByColumnAndRow($col, $row, $resource['nameShort']);
                $col++;

                foreach ($resource['weeks'] as $week => $weekData) {
                    $percentage = round($weekData['availableTime'] != 0 ? $weekData['affectedTime'] / $weekData['availableTime'] * 100 : 0, 2);
                    $sheet->setCellValueByColumnAndRow($col, $row, $percentage);
                    $col++;
                }

                $row++;
                $col = 0;
            }

            // Print the team charges
            $sheet->setCellValueByColumnAndRow($col, $row, $teamData['name']);
            $col++;

            foreach ($teamData['charge'] as $week => $weekData) {
                $percentage = round($weekData['availableTime'] != 0 ? $weekData['affectedTime'] / $weekData['availableTime'] * 100 : 0, 2);
                $sheet->setCellValueByColumnAndRow($col, $row, $percentage);
                $col++;
            }

            // Remember where the serie data resides
            $dataSeriesLabels[] = new \PHPExcel_Chart_DataSeriesValues('String', 'Worksheet!$A$' . $row, null, 1);
            $dataSeriesValues[] = new \PHPExcel_Chart_DataSeriesValues('Number', 'Worksheet!$B$' . $row . ':$' . \PHPExcel_Cell::stringFromColumnIndex($col - 1) . '$' . $row, null, 3);

            $row++;
            $col = 0;
        }

        // Last row is the global total
        $sheet->setCellValueByColumnAndRow($col, $row, $this->translator->trans('total'));
        $col++;

        foreach ($data['charge'] as $week => $weekData) {
            $percentage = round($weekData['availableTime'] != 0 ? $weekData['affectedTime'] / $weekData['availableTime'] * 100 : 0, 2);
            $sheet->setCellValueByColumnAndRow($col, $row, $percentage);
            $col++;
        }

        // Also add the total to the data series
        $dataSeriesLabels[] = new \PHPExcel_Chart_DataSeriesValues('String', 'Worksheet!$A$' . $row, null, 1);
        $dataSeriesValues[] = new \PHPExcel_Chart_DataSeriesValues('Number', 'Worksheet!$B$' . $row . ':$' . \PHPExcel_Cell::stringFromColumnIndex($col - 1) . '$' . $row, null, 3);

        // Initialize the chart
        $series = new \PHPExcel_Chart_DataSeries(
            \PHPExcel_Chart_DataSeries::TYPE_LINECHART,         // plotType
            \PHPExcel_Chart_DataSeries::GROUPING_STANDARD,      // plotGrouping
            range(0, count($dataSeriesValues)-1),               // plotOrder
            $dataSeriesLabels,                                  // plotLabel
            $xAxisTickValues,                                   // plotCategory
            $dataSeriesValues                                   // plotValues
        );

        $plotArea   = new \PHPExcel_Chart_PlotArea(null, array($series));
        $legend     = new \PHPExcel_Chart_Legend(\PHPExcel_Chart_Legend::POSITION_TOPRIGHT, null, false);
        $title      = new \PHPExcel_Chart_Title($filename);
        $yAxisLabel = new \PHPExcel_Chart_Title('%');

        $chart = new \PHPExcel_Chart(
            'chart1',       // name
            $title,         // title
            $legend,        // legend
            $plotArea,      // plotArea
            true,           // plotVisibleOnly
            0,              // displayBlanksAs
            NULL,           // xAxisLabel
            $yAxisLabel     // yAxisLabel
        );

        // Position the chart
        $chart->setTopLeftPosition('B' . ($row + 2));
        $chart->setBottomRightPosition('Y' . ($row + 32));

        // Add the chart to the worksheet
        $sheet->addChart($chart);
        $objWriter->setIncludeCharts(true);

        // Write the content in a buffer and return it
        ob_start();
        $objWriter->save('php://output');
        $content = ob_get_clean();

        // Return the response
        return new Response(
          $content,
          200,
          array(
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet; charset=utf-8',
            'Content-Disposition' => 'attachment; filename="'.$filename.'.xlsx"',
            'Content-Transfer-Encoding' => 'application/octet-stream',
            'Content-Length' => strlen($content)
          )
        );
    }
}
