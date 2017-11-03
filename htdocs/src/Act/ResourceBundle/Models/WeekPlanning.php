<?php

namespace Act\ResourceBundle\Models;

use Act\ResourceBundle\Entity\Resource;

/**
 * Class WeekPlanning
 *
 * Contain all data to display a week planning
 * of all assignments for a given resource.
 *
 */
class WeekPlanning
{
    private $resource;
    private $start;
    private $period;
    private $projects;
    private $data;

    public function setResource(Resource $resource)
    {
        $this->resource = $resource;
    }

    public function setStart(\DateTime $start)
    {
        $this->start = $start;
    }

    public function setPeriod(\DatePeriod $p)
    {
        $this->period = $p;
    }

    public function setProjects(array $projects)
    {
        $this->projects = $projects;
    }

    public function setData(array $data)
    {
        $this->data = $data;
    }

    public function getPeriod()
    {
        return $this->period;
    }

    public function getStart()
    {
        return $this->start;
    }

    public function getProjects()
    {
        return $this->projects;
    }

    public function getData()
    {
        return $this->data;
    }
}
