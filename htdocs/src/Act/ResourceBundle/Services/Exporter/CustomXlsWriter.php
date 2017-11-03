<?php

namespace Act\ResourceBundle\Services\Exporter;

use Exporter\Writer\WriterInterface;

class CustomXlsWriter implements WriterInterface
{
    protected $filename;
    protected $file;
    protected $showHeaders;
    protected $position;
    protected $resources;

    /**
     * @throws \RuntimeException
     *
     * @param      $filename
     * @param bool $showHeaders
     */
    public function __construct($filename, $showHeaders = true)
    {
        $this->filename    = $filename;
        $this->showHeaders = $showHeaders;
        $this->position    = 0;

        if (is_file($filename)) {
            throw new \RuntimeException(sprintf('The file %s already exist', $filename));
        }
    }

    /**
     * {@inheritdoc}
     */
    public function open()
    {
        $this->file = fopen($this->filename, 'w', false);
        fwrite($this->file, "<html><head><meta http-equiv=\"Content-Type\" content=\"text/html; charset=utf-8\" /><meta name=ProgId content=Excel.Sheet><meta name=Generator content=\"https://github.com/sonata-project/exporter\"></head><body><table border='solid 1'>");
    }

    /**
     * {@inheritdoc}
     */
    public function close()
    {
        fwrite($this->file, "</table></body></html>");
        fclose($this->file);
    }

    /**
     * {@inheritdoc}
     */
    public function write(array $data)
    {
        $this->init($data);

        // Create a table with all metatask, commontask and substask present in this project.
        $exceltable = "";
        foreach ($data['metatasks'] as $metatask) {
            $exceltable = $exceltable.'<tr><td>'.$metatask->getNameForExcel().'</td>'
                .'<td>'.$metatask->getStart()->format('d/m/Y').'</td>'
                .'<td>'.$metatask->getEnd()->format('d/m/Y').'</td>'
                .'<td>'.$metatask->getWorkloadSold().'</td>'
                .'<td>'.$metatask->getSumWorkloadAssigned().'</td>'
                .'<td>'.$metatask->formatTeamsAndProfilesForExcel().'</td>'
                .'<td>'.join(', ', $metatask->getAllResourcesAssigned()).'</td>'
                .'</tr>';

            foreach ($metatask->getCommontasks() as $commontask) {
                $exceltable = $exceltable.'<tr><td>'.$commontask->getNameForExcel().'</td>'
                    .'<td>'.$commontask->getStart()->format('d/m/Y').'</td>'
                    .'<td>'.$commontask->getEnd()->format('d/m/Y').'</td>'
                    .'<td>'.$commontask->getWorkloadSold().'</td>'
                    .'<td>'.$commontask->getSumWorkloadAssigned().'</td>'
                    .'<td>'.$commontask->formatTeamsAndProfilesForExcel().'</td>'
                    .'<td>'.join(', ', $commontask->getAllResourcesAssigned()).'</td>'
                    .'</tr>';

                foreach ($commontask->getSubtasks() as $subtask) {
                    $exceltable = $exceltable.'<tr><td>'.$subtask->getNameForExcel().'</td>'
                        .'<td>'.$subtask->getStart()->format('d/m/Y').'</td>'
                        .'<td>'.$subtask->getEnd()->format('d/m/Y').'</td>'
                        .'<td>'.$subtask->getWorkloadSold().'</td>'
                        .'<td>'.$subtask->getSumWorkloadAssigned().'</td>'
                        .'<td>'.$subtask->formatTeamsAndProfilesForExcel().'</td>'
                        .'<td>'.join(', ', $subtask->getAllResourcesAssigned()).'</td>'
                        .'</tr>';
                }
            }
        }

        // First for the name project, use different style.
        fwrite($this->file, '<tr>');
        fwrite($this->file, sprintf('<td style="background-color: #000000;color:#FFFFFF; font-weight: bold" colspan="7">%s</td>', $data['name']));
        fwrite($this->file, '</tr>');

        // Write exceltable.
        fwrite($this->file, $exceltable);

        $this->position++;
    }

    /**
     * @param $data
     *
     * @return array mixed
     */
    protected function init($data)
    {
        if ($this->position > 0) {
            return;
        }

        // Create the first line of the file.
        if ($this->showHeaders) {
            fwrite($this->file, '<tr>');

            foreach ($data['columns'] as $header) {
                fwrite($this->file, sprintf('<th>%s</th>', $header));
            }

            fwrite($this->file, '</tr>');
            $this->position++;
        }
    }
}
