<?php

namespace WideBundle\Setting;

use VBee\SettingBundle\Entity\Setting;
use VBee\SettingBundle\Enum\SettingTypeEnum;
use VBee\SettingBundle\Manager\SettingDoctrineManager;

/**
 * Class SettingHandler
 * @package WideBundle\Setting
 */
class SettingHandler
{
    /** @var SettingDoctrineManager $settingsManager */
    private $settingsManager;

    /**
     * SettingHandler constructor.
     * @param SettingDoctrineManager $settingsManager
     */
    public function __construct(SettingDoctrineManager $settingsManager)
    {
        $this->settingsManager = $settingsManager;
    }

    /**
     * Updates a setting value, checking for invalid values.
     *
     * @param $setting
     * @param $value
     * @return array
     */
    public function updateSetting($setting, $value)
    {
        try {
            $this->validateValue($setting, $value);
            $formattedValue = $this->formatValueIfDate($setting, $value);
            $this->settingsManager->set($setting, $formattedValue);
        } catch (\Exception $exception) {
            return ['success' => false, 'error' => $exception->getMessage()];
        }

        return ['success' => true, 'value' => $formattedValue];
    }

    /**
     * Validates the provided setting value.
     *
     * @param $settingName
     * @param $value
     * @throws \InvalidArgumentException
     */
    private function validateValue($settingName, $value)
    {
        /** @var Setting $setting */
        $setting = $this->getSettingByName($settingName);
        if ($value === null) {
            throw new \InvalidArgumentException('Null values not allowed.');
        }
        $this->checkTypeOfValue($value, $setting->getType());
        if (strpos($settingName, '_enabled') !== false && !in_array($value, ['0', '1'])) {
            throw new \InvalidArgumentException('Invalid value for boolean setting. Must be set to either 0 or 1.');
        }
    }

    /**
     * Returns a formatted version of the specified value if the setting type is `date`. Formatting date values
     * is necessary due to the poor date handling that the SettingBundle offers.
     *
     * @param $settingName
     * @param $value
     * @return mixed
     */
    private function formatValueIfDate($settingName, $value)
    {
        $formattedValue = $value;
        /** @var Setting $setting */
        $setting = $this->getSettingByName($settingName);

        if ($setting->getType() === SettingTypeEnum::DATE && $value != '') {
            $date = new \DateTime($value);
            $date->add(new \DateInterval('PT23H59M'));
            $formattedValue = $date->format('Y-m-d H:i');

        }

        return $formattedValue;
    }

    /**
     * Returns the setting specified by name parameter.
     *
     * @param $name
     * @return Setting
     * @throws \InvalidArgumentException
     */
    private function getSettingByName($name)
    {
        $settings = $this->settingsManager->all();
        foreach ($settings as $setting) {
            /** @var Setting $setting */
            if ($setting->getName() == $name) {
                return $setting;
            }
        }
        throw new \InvalidArgumentException('Setting not found in database.');
    }

    /**
     * Makes sure the provided value matches the setting type. Applies to integer and string values.
     *
     * @param $value
     * @param $type
     */
    private function checkTypeOfValue($value, $type)
    {
        $message = 'Setting type does not match the type of value stored in database.';
        if (!is_numeric($value) && $type === SettingTypeEnum::INTEGER) {
            throw new \InvalidArgumentException($message);
        }
        if ($type === SettingTypeEnum::INTEGER && intval($value) < 0) {
            throw new \InvalidArgumentException('Settings must have non-negative values.');
        }
        if (!is_string($value) && $type === SettingTypeEnum::STRING) {
            throw new \InvalidArgumentException($message);
        }
    }
}
