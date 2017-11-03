<?php

namespace Act\MainBundle\Twig;

use Act\MainBundle\Services\ColorManager;
use Act\MainBundle\Services\TimeManager;

/**
 * Class ActExtension
 *
 * Extends twig with custom filters and functions.
 *
 * @package Act\MainBundle\Twig
 */
class ActExtension extends \Twig_Extension
{
    // Dependencies.
    protected $colorManager;
    protected $timeManager;

    // Inject dependencies.
    public function __construct(ColorManager $colorManager, TimeManager $timeManager)
    {
        $this->colorManager = $colorManager;
        $this->timeManager = $timeManager;
    }
    
    /**
     * @inheritDoc
     */
    public function getFilters()
    {
        return array(
          new \Twig_SimpleFilter('rgbColor', array($this, 'rgbParse')),
          new \Twig_SimpleFilter('hexaColor', array($this, 'hexaParse')),
          new \Twig_SimpleFilter('workloadFormat', array($this, 'workloadFormat')),
        );
    }

    /**
     * Transform an hexadecimal color into a RGB string.
     *
     * @param string $colorHexa
     *   The color in hexadecimal format.
     *
     * @return string
     *   The color in RGB format.
     */
    public function rgbParse($colorHexa)
    {
        return $this->colorManager->hexaToRgb($colorHexa);
    }

    /**
     * Transform an RGB color into a hexadecimal string.
     *
     * @param string $colorRGB
     *   The color in RGB format.
     *
     * @return string
     *   The color in hexadecimal format.
     */
    public function hexaParse($colorRGB)
    {
        return $this->colorManager->rgbToHexa($colorRGB);
    }

    /**
     * Returns human readable value of a workload in day fraction.
     *
     * @param float $workload
     *   The workload in day fraction.
     * @param float $hoursPerDays
     *   The number of hours to consider for one day.
     * @param boolean $approximate
     *   Approximate to the closest quarter if wanted.
     *
     * @return string The workload in hours.
     *   The workload in hours.
     */
    public function workloadFormat($workload, $hoursPerDay = 7, $approximate = true)
    {
        return $this->timeManager->workloadFormat($workload, $hoursPerDay, $approximate);
    }

    /**
     * @inheritDoc
     */
    public function getName()
    {
        return 'act_extension';
    }
}