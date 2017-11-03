<?php

namespace Act\ResourceBundle\Services\Exporter;

use Exporter\Source\SourceIteratorInterface;
use Exporter\Writer\WriterInterface;

class CustomHandler
{
    protected $source;
    protected $writer;

    public function __construct(SourceIteratorInterface $source, WriterInterface $writer, $container)
    {
        $this->source = $source;
        $this->writer = $writer;
        $this->container = $container;
    }

    public function export()
    {
        $this->writer->open();

        foreach ($this->source as $data) {
            // Set Custom columns name on the first line.
            $data['columns']= array(
                $this->container->get('translator')->trans('task.name'),
                $this->container->get('translator')->trans('start'),
                $this->container->get('translator')->trans('end'),
                $this->container->get('translator')->trans('workload.sold'),
                $this->container->get('translator')->trans('workload.affected'),
                $this->container->get('translator')->trans('teams.profiles'),
                $this->container->get('translator')->trans('resources.assigned')
            );

            // Get metatasks for the current project in $data
            $data['metatasks'] = $this->container->get('doctrine')->getRepository('ActResourceBundle:MetaTask')->getMetaTasksForProjectArray($data);
            $this->writer->write($data);
        }

        $this->writer->close();
    }

    public function exportWithDate($date)
    {
        $this->writer->open();

        foreach ($this->source as $data) {
            // Set Custom columns name on the first line.
            $data['columns']= array(
                $this->container->get('translator')->trans('task.name'),
                $this->container->get('translator')->trans('start'),
                $this->container->get('translator')->trans('end'),
                $this->container->get('translator')->trans('workload.sold'),
                $this->container->get('translator')->trans('workload.affected'),
                $this->container->get('translator')->trans('teams.profiles'),
                $this->container->get('translator')->trans('resources.assigned')
            );

            // Get metatasks for the current project in $data and for dates.
            $data['metatasks'] = $this->container->get('doctrine')->getRepository('ActResourceBundle:MetaTask')->getMetaTasksForProjectArrayLaterThanDate($data, $date);
            $this->writer->write($data);
        }

        $this->writer->close();
    }

    public static function create(SourceIteratorInterface $source, WriterInterface $writer, $container)
    {
        return new self($source, $writer, $container);
    }
}
