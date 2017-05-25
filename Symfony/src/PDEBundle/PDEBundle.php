<?php

namespace PDEBundle;

use Symfony\Component\HttpKernel\Bundle\Bundle;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use PDEBundle\DependencyInjection\Compiler\OverrideServiceCompilerPass;

/**
 * Class PDEBundle
 * @package PDEBundle
 */
class PDEBundle extends Bundle
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
