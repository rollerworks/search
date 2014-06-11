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
use Rollerworks\Bundle\SearchBundle\DependencyInjection\Compiler\ExporterPass;
use Rollerworks\Bundle\SearchBundle\DependencyInjection\Compiler\InputProcessorPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;

class ExporterPassPassTest extends AbstractCompilerPassTestCase
{
    public function testRegisteringExporterPass()
    {
        $collectingService = new Definition();
        $collectingService->setArguments(array(null, array()));

        $this->setDefinition('rollerworks_search.exporter_factory', $collectingService);

        $collectedService = new Definition();
        $collectedService->addTag('rollerworks_search.exporter', array('alias' => 'jsonp'));
        $this->setDefinition('acme_user.search.exporter.jsonp', $collectedService);

        $this->compile();

        $collectingService = $this->container->findDefinition('rollerworks_search.exporter_factory');

        $this->assertNull($collectingService->getArgument(0));
        $this->assertEquals($collectingService->getArgument(1), array('jsonp' => 'acme_user.search.exporter.jsonp'));
    }

    protected function registerCompilerPass(ContainerBuilder $container)
    {
        $container->addCompilerPass(new ExporterPass());
    }
}
