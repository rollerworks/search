<?php

/**
 * This file is part of the RollerworksRecordFilterBundle.
 *
 * (c) Rollerscapes
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @link    http://projects.rollerscapes.net/RollerFramework
 * @license http://www.opensource.org/licenses/lgpl-license.php LGPL
 */

namespace Rollerworks\RecordFilterBundle\Tests\DependencyInjection;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\Config\Definition\Processor;

use Rollerworks\RecordFilterBundle\Tests\TestCase;
use Rollerworks\RecordFilterBundle\DependencyInjection\RollerworksRecordFilterExtension;

class RecordFilterExtensionTest extends TestCase
{
    public function testLoadDefaultConfiguration()
    {
        $container = $this->createContainer();
        $container->registerExtension(new RollerworksRecordFilterExtension());
        $container->loadFromExtension('rollerworks_record_filter', array());
        $this->compileContainer($container);

        $this->assertEquals('Rollerworks\\RecordFilterBundle\\Factory\\FormatterFactory', $container->getParameter('rollerworks_record_filter.formatter_factory.class'), '->load() loads the services.xml file');
        $this->assertEquals('Rollerworks\\RecordFilterBundle\\Factory\\SQLStructFactory', $container->getParameter('rollerworks_record_filter.sqlstruct_factory.class'), '->load() loads the services.xml file');
        $this->assertEquals('Rollerworks\\RecordFilterBundle\\Factory\\QueryBuilderFactory', $container->getParameter('rollerworks_record_filter.querybuilder_factory.class'), '->load() loads the services.xml file');
        $this->assertEquals('Rollerworks\\RecordFilterBundle\\CacheWarmer\\RecordFilterCacheWarmer', $container->getParameter('rollerworks_record_filter.cache_warmer.class'), '->load() loads the services.xml file');

        // options
        $this->assertEquals('RecordFilter', $container->getParameter('rollerworks_record_filter.filters_namespace'));
        $this->assertEquals($container->getParameter('kernel.cache_dir') . '/record_filters', $container->getParameter('rollerworks_record_filter.filters_directory'));

        $this->assertFalse($container->getParameter('rollerworks_record_filter.formatter_factory.auto_generate'));
        $this->assertFalse($container->getParameter('rollerworks_record_filter.sqlstruct_factory.auto_generate'));
        $this->assertFalse($container->getParameter('rollerworks_record_filter.querybuilder_factory.auto_generate'));
    }

    public function testLoadConfigurationFormatter()
    {
        $container = $this->createContainer();
        $container->registerExtension(new RollerworksRecordFilterExtension());
        $container->loadFromExtension('rollerworks_record_filter', array('generate_formatters' => true));
        $this->compileContainer($container);

        // options
        $this->assertEquals('RecordFilter', $container->getParameter('rollerworks_record_filter.filters_namespace'));
        $this->assertEquals($container->getParameter('kernel.cache_dir') . '/record_filters', $container->getParameter('rollerworks_record_filter.filters_directory'));

        $this->assertTrue($container->getParameter('rollerworks_record_filter.formatter_factory.auto_generate'));
        $this->assertFalse($container->getParameter('rollerworks_record_filter.sqlstruct_factory.auto_generate'));
        $this->assertFalse($container->getParameter('rollerworks_record_filter.querybuilder_factory.auto_generate'));
    }

    public function testLoadConfigurationSQLStruct()
    {
        $container = $this->createContainer();
        $container->registerExtension(new RollerworksRecordFilterExtension());
        $container->loadFromExtension('rollerworks_record_filter', array('generate_sqlstructs' => true));
        $this->compileContainer($container);

        // options
        $this->assertEquals('RecordFilter', $container->getParameter('rollerworks_record_filter.filters_namespace'));
        $this->assertEquals($container->getParameter('kernel.cache_dir') . '/record_filters', $container->getParameter('rollerworks_record_filter.filters_directory'));

        $this->assertFalse($container->getParameter('rollerworks_record_filter.formatter_factory.auto_generate'));
        $this->assertTrue($container->getParameter('rollerworks_record_filter.sqlstruct_factory.auto_generate'));
        $this->assertFalse($container->getParameter('rollerworks_record_filter.querybuilder_factory.auto_generate'));
    }

    public function testLoadConfigurationQueryBuilder()
    {
        $container = $this->createContainer();
        $container->registerExtension(new RollerworksRecordFilterExtension());
        $container->loadFromExtension('rollerworks_record_filter', array('generate_querybuilders' => true));
        $this->compileContainer($container);

        // options
        $this->assertEquals('RecordFilter', $container->getParameter('rollerworks_record_filter.filters_namespace'));
        $this->assertEquals($container->getParameter('kernel.cache_dir') . '/record_filters', $container->getParameter('rollerworks_record_filter.filters_directory'));

        $this->assertFalse($container->getParameter('rollerworks_record_filter.formatter_factory.auto_generate'));
        $this->assertFalse($container->getParameter('rollerworks_record_filter.sqlstruct_factory.auto_generate'));
        $this->assertTrue($container->getParameter('rollerworks_record_filter.querybuilder_factory.auto_generate'));
    }


    public function testLoadConfigurationDir()
    {
        $container = $this->createContainer();
        $container->registerExtension(new RollerworksRecordFilterExtension());
        $container->loadFromExtension('rollerworks_record_filter', array('filters_directory' => '%kernel.cache_dir%/doctrine/record_filters'));
        $this->compileContainer($container);

        // options
        $this->assertEquals('RecordFilter', $container->getParameter('rollerworks_record_filter.filters_namespace'));
        $this->assertEquals($container->getParameter('kernel.cache_dir') . '/doctrine/record_filters', $container->getParameter('rollerworks_record_filter.filters_directory'));

        $this->assertFalse($container->getParameter('rollerworks_record_filter.formatter_factory.auto_generate'));
        $this->assertFalse($container->getParameter('rollerworks_record_filter.sqlstruct_factory.auto_generate'));
        $this->assertFalse($container->getParameter('rollerworks_record_filter.querybuilder_factory.auto_generate'));
    }

    public function testLoadConfigurationNS()
    {
        $container = $this->createContainer();
        $container->registerExtension(new RollerworksRecordFilterExtension());
        $container->loadFromExtension('rollerworks_record_filter', array('filters_namespace' => 'RecordFilter2'));
        $this->compileContainer($container);

        // options
        $this->assertEquals('RecordFilter2', $container->getParameter('rollerworks_record_filter.filters_namespace'));
        $this->assertEquals($container->getParameter('kernel.cache_dir') . '/record_filters', $container->getParameter('rollerworks_record_filter.filters_directory'));

        $this->assertFalse($container->getParameter('rollerworks_record_filter.formatter_factory.auto_generate'));
        $this->assertFalse($container->getParameter('rollerworks_record_filter.sqlstruct_factory.auto_generate'));
        $this->assertFalse($container->getParameter('rollerworks_record_filter.querybuilder_factory.auto_generate'));
    }

    private function compileContainer(ContainerBuilder $container)
    {
        $container->getCompilerPassConfig()->setOptimizationPasses(array());
        $container->getCompilerPassConfig()->setRemovingPasses(array());
        $container->compile();
    }
}