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
use Rollerworks\Bundle\SearchBundle\DependencyInjection\Compiler\ExtensionPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;

class ExtensionPassTest extends AbstractCompilerPassTestCase
{
    public function testRegisteringOfSearchTypes()
    {
        $collectingService = new Definition();
        $collectingService->setArguments(array(null, array(), array()));

        $this->setDefinition('rollerworks_search.extension', $collectingService);

        $collectedService = new Definition();
        $collectedService->addTag('rollerworks_search.type', array('alias' => 'user_id'));
        $this->setDefinition('acme_user.search.type.user_id', $collectedService);

        $this->compile();

        $collectingService = $this->container->findDefinition('rollerworks_search.extension');

        $this->assertNull($collectingService->getArgument(0));
        $this->assertEquals($collectingService->getArgument(1), array('user_id' => 'acme_user.search.type.user_id'));
        $this->assertCount(0, $collectingService->getArgument(2));
    }

    public function testRegisteringOfSearchTypesExtensions()
    {
        $collectingService = new Definition();
        $collectingService->setArguments(array(null, array(), array()));

        $this->setDefinition('rollerworks_search.extension', $collectingService);

        $collectedService = new Definition();
        $collectedService->addTag('rollerworks_search.type_extension', array('alias' => 'field'));
        $this->setDefinition('acme_user.search.type_extension.field', $collectedService);

        $this->compile();

        $collectingService = $this->container->findDefinition('rollerworks_search.extension');

        $this->assertNull($collectingService->getArgument(0));
        $this->assertCount(0, $collectingService->getArgument(1));
        $this->assertEquals($collectingService->getArgument(2), array('field' => array('acme_user.search.type_extension.field')));
    }

    protected function registerCompilerPass(ContainerBuilder $container)
    {
        $container->addCompilerPass(new ExtensionPass());
    }
}
