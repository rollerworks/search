<?php

/**
 * This file is part of the RollerworksRecordFilterBundle.
 *
 * (c) Sebastiaan Stok <s.stok@rollerscapes.net>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Rollerworks\Bundle\RecordFilterBundle\Tests\CacheWarmer;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;
use Rollerworks\Bundle\RecordFilterBundle\DependencyInjection\RollerworksRecordFilterExtension;
use Rollerworks\Bundle\RecordFilterBundle\CacheWarmer\RecordFilterFactoriesCacheWarmer;
use Rollerworks\Bundle\RecordFilterBundle\Mapping\Loader\AnnotationDriver;
use Rollerworks\Bundle\RecordFilterBundle\Tests\TestCase;
use Doctrine\Common\Annotations\AnnotationReader;
use Metadata\MetadataFactory;

class RecordFilterFactoriesCacheWarmerTest extends TestCase
{
    /**
     * @var RecordFilterFactoriesCacheWarmer
     */
    protected $cacheWarmer;

    protected $cacheDir;

    /**
     * @var ContainerBuilder
     */
    protected $container;

    public function testWarmUpWithDefault()
    {
        $this->compileContainer($this->container);
        $this->cacheWarmer->warmUp($this->cacheDir);

        $this->assertEquals(array(), $this->getFilesInCache());
    }

    public function testWarmUpGenerateFieldSetsNoFields()
    {
        $this->container->setParameter('rollerworks_record_filter.factories.fieldset.auto_generate', true);

        $this->compileContainer($this->container);
        $this->cacheWarmer->warmUp($this->cacheDir);

        $this->assertEquals(array(), $this->getFilesInCache());
    }

    public function testWarmUpGenerateFieldSets()
    {
        $this->container->loadFromExtension('rollerworks_record_filter', array(
            'fieldsets' => array(
                'customer' => array(
                    'fields' => array(
                        'id' => array(
                            'type' => array('name' => 'number', 'params' => array()),
                            'required' => false,
                            'accept_ranges' => false,
                            'accept_compares' => false,
                        ),
                    ),
                    'import' => array(),
                )
            ),

            'factories' => array(
                'fieldset' => array('auto_generate' => true),
            )
        ));

        $this->compileContainer($this->container);
        $this->cacheWarmer->warmUp($this->cacheDir);

        $this->assertEquals(array('customer/FieldSet.php'), $this->getFilesInCache());
    }

    public function testWarmUpGenerateSqlWhereBuilder()
    {
        $this->container->loadFromExtension('rollerworks_record_filter', array(
            'fieldsets' => array(
                'customer' => array(
                    'fields' => array(
                        'id' => array(
                            'type' => array('name' => 'number', 'params' => array()),
                            'required' => false,
                            'accept_ranges' => false,
                            'accept_compares' => false,
                        ),
                    ),
                    'import' => array(),
                ),
                'invoice' => array(
                    'fields' => array(
                        'id' => array(
                            'type' => array('name' => 'number', 'params' => array()),
                            'required' => false,
                            'accept_ranges' => false,
                            'accept_compares' => false,
                        ),
                    ),
                    'import' => array(),
                )
            ),

            'factories' => array(
                'fieldset' => array('auto_generate' => true),
                'sql_wherebuilder' => array('auto_generate' => true),
            )
        ));

        $this->compileContainer($this->container);
        $this->cacheWarmer->warmUp($this->cacheDir);

        $this->assertEquals(array(
            'customer/FieldSet.php',
            'customer/SqlWhereBuilder.php',
            'invoice/FieldSet.php',
            'invoice/SqlWhereBuilder.php'
        ), $this->getFilesInCache());
    }

    protected function setUp()
    {
        parent::setUp();

        $this->container = $this->createContainer();
        $this->container->registerExtension(new RollerworksRecordFilterExtension());
        $this->container->loadFromExtension('rollerworks_record_filter', array());

        $this->container->setParameter('doctrine.default_entity_manager', 'default');
        $this->container->set('translator', $this->translator);
        //$this->container->set('templating', $this->getTwigInstance());
        $this->cacheDir = $this->container->getParameter('kernel.cache_dir');

        $conn = array(
            'driverClass' => 'Doctrine\Tests\Mocks\DriverMock',
            'wrapperClass' => 'Doctrine\Tests\Mocks\ConnectionMock',
            'user' => 'john',
            'password' => 'wayne'
        );

        $connObj = new Definition();
        $connObj->setFactoryClass('Doctrine\DBAL\DriverManager');
        $connObj->setFactoryMethod('getConnection');
        $connObj->setArguments(array($conn));
        $this->container->setDefinition('doctrine.orm.default_connection', $connObj);

        $em = new Definition();
        $em->setFactoryClass('Doctrine\Tests\Mocks\EntityManagerMock');
        $em->setFactoryMethod('create');
        $em->setArguments(array(new Reference('doctrine.orm.default_connection')));
        $this->container->setDefinition('doctrine.orm.default_entity_manager', $em);

        $annotationReader = new AnnotationReader();
        $this->container->set('annotation_reader', $annotationReader);

        $metadataFactory = new MetadataFactory(new AnnotationDriver($annotationReader));
        $this->cacheWarmer = new RecordFilterFactoriesCacheWarmer($this->container, $metadataFactory);
        $this->tearDown();
    }

    protected function tearDown()
    {
        $cacheDir = $this->container->getParameter('kernel.cache_dir') . '/record_filter';

        if (!file_exists($cacheDir)) {
            return;
        }

        $this->removeDirectory($cacheDir);
    }

    /**
     * Returns an array with all the files currently in the cache folder.
     *
     * @param string $filename
     *
     * @return array
     */
    private function getFilesInCache($filename = null)
    {
        $cacheDir = $this->container->getParameter('kernel.cache_dir') . '/record_filter';

        $this->assertTrue((file_exists($cacheDir) && is_dir($cacheDir)));

        $foundFiles = array();

        /** @var \RecursiveDirectoryIterator $path */
        foreach (new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($cacheDir), \RecursiveIteratorIterator::CHILD_FIRST) as $path) {
            if ($filename !== null && $path->getFilename() !== $filename) {
                continue;
            }

            if ($path->isFile()) {
                $foundFiles[] = substr(str_replace('\\', '/', $path->getPathname()), strlen($this->cacheDir)+15);
            }
        }

        return $foundFiles;
    }
}
