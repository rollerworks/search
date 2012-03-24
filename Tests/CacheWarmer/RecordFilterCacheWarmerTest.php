<?php

/**
 * This file is part of the RollerworksRecordFilterBundle.
 *
 * (c) Rollerscapes
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU Lesser General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Lesser General Public License for more details.
 *
 * You should have received a copy of the GNU Lesser General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 * @link    http://projects.rollerscapes.net/RollerFramework
 * @license http://www.opensource.org/licenses/lgpl-license.php LGPL
 */

namespace Rollerworks\RecordFilterBundle\Tests\CacheWarmer;

use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\DependencyInjection\Parameter;
use Symfony\Component\DependencyInjection\Dumper\PhpDumper;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBag;
use Symfony\Component\DependencyInjection\Scope;

use Rollerworks\RecordFilterBundle\CacheWarmer\RecordFilterCacheWarmer;

use Rollerworks\RecordFilterBundle\Tests\Factory\FactoryTestCase;
use Rollerworks\RecordFilterBundle\Tests\Fixtures\BaseBundle\BaseBundle;
use Rollerworks\RecordFilterBundle\Tests\Fixtures\TestBundle\TestBundle;

class RecordFilterCacheWarmerTest extends FactoryTestCase
{
    /**
     * @var \Symfony\Component\DependencyInjection\ContainerBuilder
     */
    protected $container;

    /**
     * @var \Rollerworks\FrameworkBundle\CacheWarmer\RecordFilterCacheWarmer
     */
    protected $cacheWarmer;

    protected $cacheDir;

    protected function setUp()
    {
        parent::setUp();

        $kernel = $this
            ->getMockBuilder('Symfony\Component\HttpKernel\Kernel')
            ->disableOriginalConstructor()
            ->getMock()
        ;

        $kernel
            ->expects($this->any())
            ->method('getBundle')
        ;

        $kernel
            ->expects($this->any())
            ->method('getBundles')
            ->will($this->returnValue(array(
                'BaseBundle' => new BaseBundle(),
                'TestBundle' => new TestBundle()
            )))
        ;

        /** @var \Symfony\Component\HttpKernel\KernelInterface $kernel */
        $this->container = $this->createContainer();

        $this->container->set('kernel', $kernel);
        $this->container->set('translator', $this->translator);
        $this->container->set('templating', $this->getTwigInstance());

        $this->container->setParameter('kernel.bundles', array(
            'BaseBundle' => 'Rollerworks\RecordFilterBundle\Tests\Fixtures\BaseBundle\BaseBundle',
            'TestBundle' => 'Rollerworks\RecordFilterBundle\Tests\Fixtures\TestBundle\TestBundle'
        ));

        // Default config
        $this->container->setParameter('rollerworks_record_filter.filters_directory', $this->container->getParameter('kernel.cache_dir'). '/record_filters_cache');
        $this->container->setParameter('rollerworks_record_filter.filters_namespace', 'RecordFilter');

        $this->container->setParameter('rollerworks_record_filter.formatter_factory.auto_generate',    false);
        $this->container->setParameter('rollerworks_record_filter.sqlstruct_factory.auto_generate',    false);
        $this->container->setParameter('rollerworks_record_filter.querybuilder_factory.auto_generate', false);

        // Formatter
        $this->container->register('rollerworks_record_filter.formatter_factory', 'Rollerworks\RecordFilterBundle\Factory\FormatterFactory')
            ->addArgument(new Definition('Doctrine\Common\Annotations\AnnotationReader'))
            ->addArgument($this->container->getParameter('kernel.cache_dir'). '/record_filters_cache')
            ->addArgument($this->container->getParameter('rollerworks_record_filter.filters_namespace'))

            ->addMethodCall('setContainer',  array(new Reference('service_container')))
            ->addMethodCall('setTranslator', array(new Reference('translator')));

        // SQL-struct
        $this->container->register('rollerworks_record_filter.sqlstruct_factory', 'Rollerworks\RecordFilterBundle\Factory\SQLStructFactory')
            ->addArgument(new Definition('Doctrine\Common\Annotations\AnnotationReader'))
            ->addArgument($this->container->getParameter('kernel.cache_dir'). '/record_filters_cache')
            ->addArgument($this->container->getParameter('rollerworks_record_filter.filters_namespace'));

        // QueryBuilder
        $this->container->register('rollerworks_record_filter.querybuilder_factory', 'Rollerworks\RecordFilterBundle\Factory\QueryBuilderFactory')
            ->addArgument(new Definition('Doctrine\Common\Annotations\AnnotationReader'))
            ->addArgument($this->container->getParameter('kernel.cache_dir'). '/record_filters_cache')
            ->addArgument($this->container->getParameter('rollerworks_record_filter.filters_namespace'));

        $this->cacheWarmer = new RecordFilterCacheWarmer($kernel, $this->container);
        $this->cacheDir    = $this->container->getParameter('kernel.cache_dir');

        $this->tearDown();
    }

    protected function tearDown()
    {
        if (file_exists($this->container->getParameter('kernel.cache_dir') . '/entities_hash_mapping.php')) {
            unlink($this->container->getParameter('kernel.cache_dir') . '/entities_hash_mapping.php');
        }

        $cacheDir = $this->container->getParameter('rollerworks_record_filter.filters_directory');

        if (!file_exists($cacheDir)) {
            return;
        }

        foreach (new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($cacheDir), \RecursiveIteratorIterator::CHILD_FIRST) as $path) {
            if ($path->isDir()) {
                rmdir($path->__toString());
            }
            else {
                unlink($path->__toString());
            }
        }
    }

    public function testWarmUpWithDefault()
    {
        $this->container->compile();
        $this->cacheWarmer->warmUp($this->cacheDir);

        // We use equal so we can actually see what is created ;)
        $this->assertEquals(array(), $this->getFilesInCache());
    }

    public function testWarmUpGenerateFormatters()
    {
        $this->container->setParameter('rollerworks_record_filter.formatter_factory.auto_generate', true);

        $this->container->compile();
        $this->cacheWarmer->warmUp($this->cacheDir);

        $cacheDir = $this->container->getParameter('rollerworks_record_filter.filters_directory');
        $foundFiles = $this->getFilesInCache();

        $cacheDir = str_replace('\\', '/', $cacheDir);

        $this->assertEquals(array(
            $cacheDir . '/RollerworksRecordFilterBundleTestsFixturesBaseBundleEntityECommerceECommerceInvoice/Formatter.php',
            $cacheDir . '/RollerworksRecordFilterBundleTestsFixturesBaseBundleEntityECommerceECommerceProductCompares/Formatter.php',
            $cacheDir . '/RollerworksRecordFilterBundleTestsFixturesBaseBundleEntityECommerceECommerceProductRange/Formatter.php',
            $cacheDir . '/RollerworksRecordFilterBundleTestsFixturesBaseBundleEntityECommerceECommerceProductReq/Formatter.php',
            $cacheDir . '/RollerworksRecordFilterBundleTestsFixturesBaseBundleEntityECommerceECommerceProductSimple/Formatter.php',
            $cacheDir . '/RollerworksRecordFilterBundleTestsFixturesBaseBundleEntityECommerceECommerceProductTwo/Formatter.php',
            $cacheDir . '/RollerworksRecordFilterBundleTestsFixturesBaseBundleEntityECommerceECommerceProductType/Formatter.php',
            $cacheDir . '/RollerworksRecordFilterBundleTestsFixturesBaseBundleEntityECommerceECommerceProductWithType/Formatter.php',
            $cacheDir . '/RollerworksRecordFilterBundleTestsFixturesBaseBundleEntityECommerceECommerceProductWithType2/Formatter.php',
            $cacheDir . '/RollerworksRecordFilterBundleTestsFixturesTestBundleEntityECommerceECommerceInvoice/Formatter.php',
            $cacheDir . '/RollerworksRecordFilterBundleTestsFixturesTestBundleEntityECommerceECommerceProductCompares/Formatter.php',
            $cacheDir . '/RollerworksRecordFilterBundleTestsFixturesTestBundleEntityECommerceECommerceProductRange/Formatter.php'
        ), $foundFiles);
    }

    public function testWarmUpGenerateBueryBuilder()
    {
        $this->container->setParameter('rollerworks_record_filter.formatter_factory.auto_generate',    true);
        $this->container->setParameter('rollerworks_record_filter.querybuilder_factory.auto_generate', true);

        $this->container->compile();
        $this->cacheWarmer->warmUp($this->cacheDir);

        $cacheDir = $this->container->getParameter('rollerworks_record_filter.filters_directory');
        $foundFiles = $this->getFilesInCache('QueryBuilder.php');

        $cacheDir = str_replace('\\', '/', $cacheDir);

        $this->assertEquals(array(
            $cacheDir . '/RollerworksRecordFilterBundleTestsFixturesBaseBundleEntityECommerceECommerceInvoice/QueryBuilder.php',
            $cacheDir . '/RollerworksRecordFilterBundleTestsFixturesBaseBundleEntityECommerceECommerceProductCompares/QueryBuilder.php',
            $cacheDir . '/RollerworksRecordFilterBundleTestsFixturesBaseBundleEntityECommerceECommerceProductRange/QueryBuilder.php',
            $cacheDir . '/RollerworksRecordFilterBundleTestsFixturesBaseBundleEntityECommerceECommerceProductReq/QueryBuilder.php',
            $cacheDir . '/RollerworksRecordFilterBundleTestsFixturesBaseBundleEntityECommerceECommerceProductSimple/QueryBuilder.php',
            $cacheDir . '/RollerworksRecordFilterBundleTestsFixturesBaseBundleEntityECommerceECommerceProductTwo/QueryBuilder.php',
            $cacheDir . '/RollerworksRecordFilterBundleTestsFixturesBaseBundleEntityECommerceECommerceProductType/QueryBuilder.php',
            $cacheDir . '/RollerworksRecordFilterBundleTestsFixturesBaseBundleEntityECommerceECommerceProductWithType/QueryBuilder.php',
            $cacheDir . '/RollerworksRecordFilterBundleTestsFixturesBaseBundleEntityECommerceECommerceProductWithType2/QueryBuilder.php',
            $cacheDir . '/RollerworksRecordFilterBundleTestsFixturesTestBundleEntityECommerceECommerceInvoice/QueryBuilder.php',
            $cacheDir . '/RollerworksRecordFilterBundleTestsFixturesTestBundleEntityECommerceECommerceProductCompares/QueryBuilder.php',
            $cacheDir . '/RollerworksRecordFilterBundleTestsFixturesTestBundleEntityECommerceECommerceProductRange/QueryBuilder.php',
        ), $foundFiles);
    }

    /**
     * Returns an array with all the files currently in the cache folder.
     *
     * @param string $filename
     * @return array
     */
    protected function getFilesInCache($filename = null)
    {
        $cacheDir = $this->container->getParameter('rollerworks_record_filter.filters_directory');

        $this->assertTrue((file_exists($cacheDir) && is_dir($cacheDir)));

        $foundFiles = array();

        /** @var \RecursiveDirectoryIterator $path */
        foreach (new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($cacheDir), \RecursiveIteratorIterator::CHILD_FIRST) as $path) {
            if ($filename !== null && $path->getFilename() !== $filename) {
                continue;
            }

            if ($path->isFile()) {
                $foundFiles[] = str_replace('\\', '/', $path->getPathname());
            }
        }

        return $foundFiles;
    }
}
