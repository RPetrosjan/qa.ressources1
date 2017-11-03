<?php

namespace Act\ResourceBundle\Services\Export;

use Symfony\Component\HttpFoundation\Response;
use Liuggio\ExcelBundle\Factory;
use Symfony\Component\Translation\TranslatorInterface;
use Act\ResourceBundle\Services\Assignment\PrevisionalAssignments;

/**
 * PrevisionalAssignmentsExport
 *
 * Contains code to export the previsional assignments data
 * to an Excel file.
 */
class PrevisionalAssignmentsExport
{
    /* Dependencies */
    protected $pa;
    protected $excel;
    protected $translator;

    public function __construct(PrevisionalAssignments $pa, Factory $excel, TranslatorInterface $translator)
    {
        $this->pa = $pa;
        $this->excel = $excel;
        $this->translator = $translator;
    }

    /**
     * Export previsional assignments data into an Excel sheet
     */
    public function export()
    {
        // Init excel objects
        $objPHPExcel = $this->excel->createPHPExcelObject();
        $objPHPExcel
          ->getProperties()
          ->setCompany("Actency")
          ->setCreator("Act&Ressources")
          ->setTitle($this->translator->trans('assignments.previsional') . ' : W' . $this->pa->getWeek());

        $objWriter = $this->excel->createWriter($objPHPExcel, 'Excel2007');

        // Get active sheet and tune it
        $sheet = $objPHPExcel->getActiveSheet();
        $sheet
          ->getStyle('A1:F50')
          ->getAlignment()
          ->setWrapText(true)
          ->setVertical(\PHPExcel_Style_Alignment::VERTICAL_CENTER)
          ->setHorizontal(\PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
        $sheet->getColumnDimension('B')->setWidth(40);
        $sheet->getColumnDimension('C')->setWidth(40);
        $sheet->getColumnDimension('D')->setWidth(40);
        $sheet->getColumnDimension('E')->setWidth(40);
        $sheet->getColumnDimension('F')->setWidth(40);

        // First line is the document title
        $sheet->setCellValueByColumnAndRow(0, 1, $this->translator->trans('assignments.previsional').' W' . $this->pa->getWeek());
        $sheet->mergeCells('A1:F1');
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
        $sheet->getStyle('A1:F1')->applyFromArray($style);

        // Iterate over teams
        $row = 2;
        foreach ($this->pa->getTeams() as $team) {
            $row += 2;

            // Keep in memory the first line of this team
            $teamStartRow = $row;

            // Adding team name
            $sheet->setCellValueByColumnAndRow(0, $row, $team->getName());
            $sheet->mergeCellsByColumnAndRow(0, $row, 5, $row);
            $row++;

            // Adding days
            $col = 1;
            $sheet->setCellValueByColumnAndRow(0, $row, 'W' . $this->pa->getWeek());
            $sheet->mergeCellsByColumnAndRow(0, $row, 0, $row+1);
            foreach ($this->pa->getPeriod() as $day) {
                $sheet->setCellValueByColumnAndRow($col, $row, $day->format('l'));
                $sheet->setCellValueByColumnAndRow($col, $row+1, $day->format('d/m'));
                $col++;
            }

            // Adding resources and their assignments
            $row += 2;
            foreach ($team->getResources() as $resource) {
                if (count($resource->getAssignments()) == 0) {
                    // If no assignments, go to the next resource
                    continue;
                }

                // Write the resource name short
                $sheet->setCellValueByColumnAndRow(0, $row, $resource->getNameShort());
                $col = 1;

                // For each day, write assignments/bankholidays
                foreach ($this->pa->getPeriod() as $day) {
                    $bankholidays = $this->pa->getBankholidays($day, $resource->getLocation());
                    $assignments = $resource->getAssignments($day);

                    if (count($bankholidays) > 0) {
                        $names = '';
                        foreach ($bankholidays as $bk) {
                            $names .= $bk->getName();
                        }

                        // Write bankholiday names
                        $sheet->setCellValueByColumnAndRow($col, $row, $bk->getName());

                    } elseif (count($assignments) > 0) {
                        $value = new \PHPExcel_RichText();

                        foreach ($assignments as $assignment) {
                            $projectTxt = $value->createTextRun($assignment->getProject()->getName());
                            $projectTxt->getFont()->setBold(true);
                            $projectTxt->getFont()->setItalic(true);

                            if ($assignment->getCommontask() != null) {
                                $value->createText(' : '.$assignment->getCommontask());
                            }

                            if ($assignment->getSubtask() != null) {
                                $value->createText(' - '.$assignment->getSubtask());
                            }

                            $value->createText(' ('.$assignment->getWorkload().') ');

                            if ($assignment->getComment() != null) {
                                $sheet->getCommentByColumnAndRow($col, $row)->getText()->createTextRun($assignment->getComment());
                            }
                        }

                        // Write the assignment data into the cell : project name, tasks, comment...
                        $sheet->setCellValueByColumnAndRow($col, $row, $value);

                        // Set the project color
                        $style = array(
                          'fill' => array(
                            'type' => \PHPExcel_Style_Fill::FILL_SOLID,
                            'color' => array('rgb' => substr($assignment->getProject()->getColor(), -6)),
                          )
                        );
                        $sheet->getStyleByColumnAndRow($col, $row)->applyFromArray($style);
                    }
                    $col++;
                }
                $row++;
            }

            // Set the team color
            $style = array(
              'fill' => array(
                'type' => \PHPExcel_Style_Fill::FILL_SOLID,
                'color' => array('rgb' => substr($team->getColor(), -6)),
              )
            );
            $sheet->getStyle('A'.$teamStartRow.':F'.($teamStartRow+2))->applyFromArray($style);
            $sheet->getStyle('A'.($teamStartRow+2).':A'.($row-1))->applyFromArray($style);

            // Set some borders
            $style = array(
              'borders' => array(
                'allborders' => array(
                  'style' => \PHPExcel_Style_Border::BORDER_THIN,
                  'color' => array('rgb' => '000000'),
                )
              )
            );
            $sheet->getStyle('A'.$teamStartRow.':F'.($row-1))->applyFromArray($style);
        }

        // Write the content in a buffer and return it
        ob_start();
        $objWriter->save('php://output');
        $content = ob_get_clean();

        // Create a filename
        $filename = '';
        if (count($this->pa->getTeams()) == 1) {
            $filename .= $team->getName().' - ';
        }
        $filename .= $this->translator->trans('assignments.previsional').' W'.$this->pa->getWeek().' - '.$this->pa->getYear();

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
