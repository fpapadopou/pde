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
     */
    private function addSetting($name, $value)
    {
        /** @var SettingDoctrineManager $settingsManager */
        $settingsManager = $this->getContainer()->get('vbee.manager.setting');
        // Setting in the context of this app are either
        $type = SettingTypeEnum::STRING;
        if (is_numeric($value['value'])) {
            $type = SettingTypeEnum::INTEGER;
        }

        $currentValue = $settingsManager->get($name);
        if ($currentValue !== null) {
            $settingsManager->set($name, $value['value'], $type);
            return;
        }

        $settingsManager->create($name, $value['value'], $type, $value['description']);

    }
}
