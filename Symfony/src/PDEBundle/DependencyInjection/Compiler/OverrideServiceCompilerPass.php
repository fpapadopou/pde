<?php

namespace PDEBundle\DependencyInjection\Compiler;

use PDEBundle\Validator\Constraints\DateValidator;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * Class OverrideServiceCompilerPass
 * @package PDEBundle\DependencyInjection\Compiler
 */
class OverrideServiceCompilerPass implements CompilerPassInterface
{
    /**
     * Overrides the date validator definition of VBee SettingBundle, using a custom DateValidator class.
     *
     * @param ContainerBuilder $container
     */
    public function process(ContainerBuilder $container)
    {
        $definition = $container->getDefinition('vbee.validator.date');
        $definition->setClass(DateValidator::class);
    }
}
