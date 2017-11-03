<?php

namespace Act\MainBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Exception\MissingOptionsException;

/**
 * @Annotation
 */
class DateRange extends Constraint
{
    public $errorMessage = 'The starting date must be before the ending date';

    public $start;
    public $end;

    public function __construct($options = null)
    {
        parent::__construct($options);

        if (null === $this->start || null === $this->end) {
            throw new MissingOptionsException('Both options "start" and "end" must be given for constraint ' . __CLASS__, array('start', 'end'));
        }
    }

    public function getTargets()
    {
        return self::CLASS_CONSTRAINT;
    }

    public function validatedBy()
    {
        return 'daterange_validator';
    }
}