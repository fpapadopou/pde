<?php

namespace WideBundle;

use Symfony\Component\HttpKernel\Bundle\Bundle;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use WideBundle\DependencyInjection\Compiler\OverrideServiceCompilerPass;

/**
 * Class WideBundle
 * @package WideBundle
 */
class WideBundle extends Bundle
{
    /**
     * Performs the actual override operation of the VBee date setting validator, once the container is compiled.
     *
     * @param ContainerBuilder $container
     */
    public function build(ContainerBuilder $container)
    {
        parent::build($container);

        $container->addCompilerPass(new OverrideServiceCompilerPass());
    }
}
