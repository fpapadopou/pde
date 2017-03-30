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
            $this->settingsManager->set($setting, $value);
        } catch (\Exception $exception) {
            return ['success' => false, 'error' => $exception->getMessage()];
        }

        return ['success' => true];
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
     * Makes sure the provided values matches the setting type. Only string and integer values are supported.
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
        if ($type === SettingTypeEnum::INTEGER && intval($value) <=0) {
            throw new \InvalidArgumentException('Settings must have non-negative values.');
        }
        if (!is_string($value) && $type === SettingTypeEnum::STRING) {
            throw new \InvalidArgumentException($message);
        }
    }
}
