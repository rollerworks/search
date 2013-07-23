<?php

/*
 * This file is part of the RollerworksRecordFilterBundle package.
 *
 * (c) Sebastiaan Stok <s.stok@rollerscapes.net>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Rollerworks\Bundle\RecordFilterBundle;

use Symfony\Component\DependencyInjection\ContainerBuilder;

use Rollerworks\Bundle\RecordFilterBundle\DependencyInjection\Compiler\AddFilterTypesPass;
use Rollerworks\Bundle\RecordFilterBundle\DependencyInjection\Compiler\FormatterModifiersPass;

/**
 * RollerworksRecordFilterBundle.
 */
class RollerworksRecordFilterBundle extends \Symfony\Component\HttpKernel\Bundle\Bundle
{
    public function build(ContainerBuilder $container)
    {
        $container->addCompilerPass(new AddFilterTypesPass());
        $container->addCompilerPass(new FormatterModifiersPass());
    }
}
