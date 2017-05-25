<?php

namespace PDEBundle\Validator\Constraints;

use VBee\SettingBundle\Validator\Constraints\SettingValueValidatorInterface;

/**
 * Class DateSettingValidator
 * @package PDEBundle\Validator\Constraints
 */
class DateValidator implements SettingValueValidatorInterface
{
    /**
     * Check if the date is either empty of in 'Y-m-d' format.
     *
     * @param $value
     * @return bool
     */
    public function validate($value)
    {
        // Empty date values allowed.
        if ($value == '') {
            return true;
        }
        // Dates must be provided in 'Y-m-d' format.
        if (\DateTime::createFromFormat('Y-m-d H:i', $value) === false) {
            return false;
        }
        return true;
    }

    /**
     * Name of the setting validated by this class.
     *
     * @return string
     */
    public function getName()
    {
        return 'date';
    }
}