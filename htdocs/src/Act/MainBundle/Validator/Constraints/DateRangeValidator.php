<?php

namespace Act\MainBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

class DateRangeValidator extends ConstraintValidator
{
    public function validate($value, Constraint $constraint)
    {
        $start = $value->{$constraint->start}();
        $end = $value->{$constraint->end}();

        if ($end < $start) {
            $this->context->addViolation($constraint->errorMessage);
            return false;
        }

        return true;
    }
}
