<?php

/**
 * This file is part of the RollerworksSearchBundle package.
 *
 * (c) 2014 Sebastiaan Stok <s.stok@rollerscapes.net>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Rollerworks\Bundle\SearchBundle;

use Rollerworks\Component\Search\Extension\Symfony\DependencyInjection\Compiler;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

class RollerworksSearchBundle extends Bundle
{
    /**
     * {@inheritdoc}
     */
    public function build(ContainerBuilder $container)
    {
        $container->addCompilerPass(new Compiler\ExtensionPass());
        $container->addCompilerPass(new Compiler\InputProcessorPass());
        $container->addCompilerPass(new Compiler\ExporterPass());
        $container->addCompilerPass(new Compiler\ConditionOptimizerPass());
    }
}
