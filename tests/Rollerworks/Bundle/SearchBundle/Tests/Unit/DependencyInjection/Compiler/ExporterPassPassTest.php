<?php

/*
 * This file is part of the RollerworksSearchBundle package.
 *
 * (c) Sebastiaan Stok <s.stok@rollerscapes.net>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
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
