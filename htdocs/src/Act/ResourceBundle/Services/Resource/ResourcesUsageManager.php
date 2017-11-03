<?php


namespace Act\ResourceBundle\Services\Resource;

use Act\MainBundle\Services\ColorManager;
use Act\MainBundle\Services\DateManager;
use Act\ResourceBundle\Entity\Resource;
use Doctrine\ORM\EntityManager;

class ResourcesUsageManager
{
    private $em;
    private $rm;
    private $tm;
    private $cm;

    public function __construct(EntityManager $em, ResourceManager $rm, DateManager $tm)
    {
        $this->em = $em;
        $this->rm = $rm;
        $this->tm = $tm;
        $this->cm = new ColorManager();
    }

    /**
     * Get the resource charge for the given period.
     *
     * @param Resource $resource
     * @param \DateTime $start
     * @param \DateTime $end
     * @param array $tags
     * @param array $projects
     *
     * @return array
     */
    public function getResourceChargeForPeriod(Resource $resource, \DateTime $start, \DateTime $end, array $tags = null, array $projects = null)
    {
        // Compute the total resource availability
        $workloadAvailable = $this->rm->getResourceTotalWorkloadAvailable($resource, $start, $end, true);

        // Only take into account tagged and selected projects
        $affectedTime = $this->em->getRepository('ActResourceBundle:Assignment')->getAssignmentsSumForResourceByProjectAndTags(
          $start,
          $end,
          $resource,
          $projects,
          $tags
        );

        // Reset if necessary
        if (is_null($affectedTime)) {
            $affectedTime = 0;
        }

        return array(
          'affectedTime' => $affectedTime,
          'availableTime' => $workloadAvailable
        );
    }

    /**
     * Get the resource charge by week for the given period.
     *
     * @param Resource $resource
     * @param \DateTime $start
     * @param \DateTime $end
     * @param array $tags
     * @param array $projects
     *
     * @return array
     */
    public function getResourceWeeklyChargeForPeriod(Resource $resource, \DateTime $start, \DateTime $end, array $tags = null, array $projects = null)
    {
        $result = array();

        // Initialize Bankholidays
        $this->rm->initializeBankHolidaysPool($start, $end);

        // Iterate over the date period
        $period = new \DatePeriod($start, \DateInterval::createFromDateString('1 week'), $end);
        foreach ($period as $week) {
            $dates = $this->tm->findFirstAndLastDaysOfWeek($week);
            $result[$dates['end']->format('W/Y')] = $this->getResourceChargeForPeriod($resource, $dates['start'], $dates['end'], $tags, $projects);
            $result[$dates['end']->format('W/Y')]['week'] = $dates['end'];
        }

        return $result;
    }

    /**
     * Generates an array of data for the resource usage page.
     *
     * @param array $resources
     * @param \DateTime $start
     * @param \DateTime $end
     * @param array $tags
     * @param array projects
     *
     * @return array
     */
    public function generateUsageData(array $resources, \DateTime $start, \DateTime $end, array $tags = null, array $projects = null)
    {
        $result = array();
        // First iteration to prepare team structure.
        foreach ($resources as $r) {
            $resource = $this->em->getRepository('ActResourceBundle:Resource')->find($r);

            $result['team'][$resource->getTeam()->getId()]['name'] = $resource->getTeam()->getName();
            $result['team'][$resource->getTeam()->getId()]['color'] = $this->cm->hexaToRgb($resource->getTeam()->getColor());
            $result['team'][$resource->getTeam()->getId()]['resources'][] = array(
              'id' => $resource->getId(),
              'name' => $resource->getName(),
              'nameShort' => $resource->getNameShort(),
              'weeks' => $this->getResourceWeeklyChargeForPeriod($resource, $start, $end, $tags, $projects)
            );
        }

        $result['startWeek'] = $start->format('W');
        $result['endWeek'] = $end->format('W');
        $result['charge'] = array(); // Global total charges

        // Second iteration to gather totals.
        foreach ($result['team'] as $id => $team) {
            $result['team'][$id]['charge'] = array();
            foreach ($team['resources'] as $resource) {
                foreach ($resource['weeks'] as $key => $week) {
                    if (!array_key_exists($key, $result['team'][$id]['charge'])) {
                        $result['team'][$id]['charge'][$key] = array('affectedTime' => 0, 'availableTime' => 0);
                    }

                    if (!array_key_exists($key, $result['charge'])) {
                        $result['charge'][$key] = array('affectedTime' => 0, 'availableTime' => 0);
                    }

                    $result['team'][$id]['charge'][$key]['availableTime'] += $week['availableTime'];
                    $result['team'][$id]['charge'][$key]['affectedTime'] += $week['affectedTime'];
                    $result['charge'][$key]['availableTime'] += $week['availableTime'];
                    $result['charge'][$key]['affectedTime'] += $week['affectedTime'];
                }
            }
        }

        return $result;
    }

}
