<?php

/*
 * This file is part of the RollerworksSearchBundle package.
 *
 * (c) Sebastiaan Stok <s.stok@rollerscapes.net>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Rollerworks\Bundle\SearchBundle;

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
        $container->addCompilerPass(new FormatterPass());
    }
}
