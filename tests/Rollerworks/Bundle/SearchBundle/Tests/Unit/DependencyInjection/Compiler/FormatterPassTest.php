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
use Rollerworks\Bundle\SearchBundle\DependencyInjection\Compiler\FormatterPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;

class FormatterPassTests extends AbstractCompilerPassTestCase
{
    public function testRegisteringFormattersInPriorityOrder()
    {
        $collectingService = new Definition();
        $collectingService->setArguments(array(null, array(), array()));

        $this->setDefinition('rollerworks_search.chain_formatter', $collectingService);

        $collectedService = new Definition();
        $collectedService->addTag('rollerworks_search.formatter');
        $this->setDefinition('acme_user.search.formatter.second', $collectedService);

        $collectedService2 = new Definition();
        $collectedService2->addTag('rollerworks_search.formatter', array('priority' => -1));
        $this->setDefinition('acme_user.search.formatter.last', $collectedService2);

        $collectedService3 = new Definition();
        $collectedService3->addTag('rollerworks_search.formatter', array('priority' => 12));
        $this->setDefinition('acme_user.search.formatter.first', $collectedService3);

        $this->compile();

        $collectingService = $this->container->findDefinition('rollerworks_search.chain_formatter');
        $calls = $collectingService->getMethodCalls();

        $expectedCalls = array(
            array('addFormatter', array(new Reference('acme_user.search.formatter.first'))),
            array('addFormatter', array(new Reference('acme_user.search.formatter.second'))),
            array('addFormatter', array(new Reference('acme_user.search.formatter.last'))),
        );

        $this->assertEquals($expectedCalls, $calls);
    }
    protected function registerCompilerPass(ContainerBuilder $container)
    {
        $container->addCompilerPass(new FormatterPass());
    }
}
