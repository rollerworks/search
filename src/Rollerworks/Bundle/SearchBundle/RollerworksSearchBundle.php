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

use Rollerworks\Bundle\SearchBundle\DependencyInjection\Compiler\ExporterPass;
use Rollerworks\Bundle\SearchBundle\DependencyInjection\Compiler\FormatterPass;
use Rollerworks\Bundle\SearchBundle\DependencyInjection\Compiler\InputProcessorPass;
use Rollerworks\Bundle\SearchBundle\DependencyInjection\Compiler\ExtensionPass;
use Rollerworks\Bundle\SearchBundle\DependencyInjection\Compiler\TranslatorPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

class RollerworksSearchBundle extends Bundle
{
    public function build(ContainerBuilder $container)
    {
        $container->addCompilerPass(new ExtensionPass());
        $container->addCompilerPass(new TranslatorPass());
        $container->addCompilerPass(new InputProcessorPass());
        $container->addCompilerPass(new ExporterPass());
        $container->addCompilerPass(new FormatterPass());
    }
}
