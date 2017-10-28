<?php

declare(strict_types=1);

/*
 * This file is part of the RollerworksSearch package.
 *
 * (c) Sebastiaan Stok <s.stok@rollerscapes.net>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Rollerworks\Bundle\SearchBundle;

use Rollerworks\Bundle\SearchBundle\DependencyInjection\Compiler;
use Symfony\Component\DependencyInjection\Compiler\PassConfig;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

class RollerworksSearchBundle extends Bundle
{
    public function build(ContainerBuilder $container)
    {
        $container->addCompilerPass(new Compiler\ExtensionPass());
        $container->addCompilerPass(new Compiler\InputProcessorPass());
        $container->addCompilerPass(new Compiler\ExporterPass());
        $container->addCompilerPass(new Compiler\ConditionOptimizerPass());
        $container->addCompilerPass(new Compiler\FieldSetRegistryPass());
        $container->addCompilerPass(new Compiler\DoctrineOrmPass());
        $container->addCompilerPass(new Compiler\DoctrineOrmQueryBuilderPass(), PassConfig::TYPE_BEFORE_REMOVING);
    }
}
