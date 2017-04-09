<?php

namespace WideBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\Container;
use VBee\SettingBundle\Manager\SettingDoctrineManager;
use VBee\SettingBundle\Enum\SettingTypeEnum;

/**
 * Class DefaultSettingsCommand
 * @package WideBundle\Command
 */
class DefaultSettingsCommand extends ContainerAwareCommand
{
    /**
     * Configures the command properties.
     */
    public function configure()
    {
        // Use the command like 'php app/console wide:settings:set'
        $this->setName('wide:settings:set');
        // Description displayed during the 'php app/console list' command
        $this->setDescription('Sets default values for configurable setting of the application.');
        $helpMessage = 'This script copies all configurable settings\' values from the parameters.yml' . "\n";
        $helpMessage .= 'file to the database \'vbee_setting\' table. Once the script is done, the' . "\n";
        $helpMessage .= 'the settings\' values can be modified from the admin panel.' . "\n";
        $helpMessage .= 'No parameter (required or optional)' . "\n";
        // Running 'php app/console wide:settings:set --help' will display this message
        $this->setHelp($helpMessage);
    }

    /**
     * Executes the command.
     *
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return void
     */
    public function execute(InputInterface $input, OutputInterface $output)
    {
        /** @var Container $container */
        $container = $this->getContainer();
        $defaultSettings = $container->getParameter('default_settings');
        if (empty($defaultSettings)) {
            $output->writeln('Invalid default settings parameter. Check configuration.');
            return;
        }
        foreach ($defaultSettings as $setting => $value) {
            $output->writeln("Setting $setting");
            try {
                $this->addSetting($setting, $value);
            } catch (\Exception $exception) {
                $output->writeln("Setting $setting failed - " . $exception->getMessage());
                return;
            }
        }

    }

    /**
     * Either creates a new setting in the database or updates the value and type of an existing one.
     *
     * @param $name
     * @param $value
     * @throws \ErrorException
     */
    private function addSetting($name, $value)
    {
        /** @var SettingDoctrineManager $settingsManager */
        $settingsManager = $this->getContainer()->get('vbee.manager.setting');
        // A valid (int/str/date) type must be specified for each setting in the parameters file.
        if (!in_array($value['type'], [SettingTypeEnum::STRING, SettingTypeEnum::INTEGER, SettingTypeEnum::DATE])) {
            throw new \ErrorException('Invalid setting type ' . $value['type'] . " for setting $name");
        }

        // Update the value if the setting already exists.
        $currentValue = $settingsManager->get($name);
        if ($currentValue !== null) {
            $settingsManager->set($name, $value['value'], $value['type']);
            return;
        }

        // Create a new setting with the specified type, value and description otherwise.
        $settingsManager->create($name, $value['value'], $value['type'], $value['description']);

    }
}
