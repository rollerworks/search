<?php

/**
 * This file is part of the RollerworksSearchBundle package.
 *
 * (c) 2014 Sebastiaan Stok <s.stok@rollerscapes.net>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Rollerworks\Bundle\SearchBundle\Tests\Unit\DependencyInjection\Compiler;

use Matthias\SymfonyDependencyInjectionTest\PhpUnit\AbstractCompilerPassTestCase;
use Rollerworks\Bundle\SearchBundle\DependencyInjection\Compiler\FieldSetRegistryPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;

class FieldSetRegistryPassTest extends AbstractCompilerPassTestCase
{
    public function testRegisteringExporterPass()
    {
        $collectingService = new Definition();
        $collectingService->setArguments(array(null, array()));

        $this->setDefinition('rollerworks_search.fieldset_registry', $collectingService);

        $collectedService = new Definition();
        $collectedService->addTag('rollerworks_search.fieldset', array('name' => 'acme_user'));
        $this->setDefinition('rollerworks_search.fieldset.acme_user', $collectedService);

        $this->compile();

        $collectingService = $this->container->findDefinition('rollerworks_search.fieldset_registry');

        $this->assertNull($collectingService->getArgument(0));
        $this->assertEquals($collectingService->getArgument(1), array('acme_user' => 'rollerworks_search.fieldset.acme_user'));
    }

    protected function registerCompilerPass(ContainerBuilder $container)
    {
        $container->addCompilerPass(new FieldSetRegistryPass());
    }
}
