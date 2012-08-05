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
use Rollerworks\Bundle\RecordFilterBundle\Mapping\FilterTypeConfig;
use Rollerworks\Bundle\RecordFilterBundle\Factory\FilterTypeFactory;
use Rollerworks\Bundle\RecordFilterBundle\Factory\FieldSetFactory;
use Rollerworks\Bundle\RecordFilterBundle\Type as FilterType;
use Rollerworks\Bundle\RecordFilterBundle\FilterField;
use Rollerworks\Bundle\RecordFilterBundle\FieldSet;
use Rollerworks\Bundle\RecordFilterBundle\Tests\TestCase;
use Rollerworks\Bundle\RecordFilterBundle\Tests\Fixtures\CustomerType;
use Rollerworks\Bundle\RecordFilterBundle\Tests\Fixtures\StatusType;
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
        $fieldName = self::getUniqueFieldName('customer');

        $this->container->loadFromExtension('rollerworks_record_filter', array(
            'fieldsets' => array(
                $fieldName => array(
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

        $this->createTypes();
        $this->compileContainer($this->container);
        $this->cacheWarmer->warmUp($this->cacheDir);

        $this->assertEquals(array($fieldName . '/FieldSet.php'), $this->getFilesInCache());

        $this->assertFieldSetEquals($fieldName, array(
            'id' => array(
                'type' => new FilterTypeConfig('number'),
                'required' => false,
                'accept_ranges' => false,
                'accept_compares' => false
            ),
        ));
    }

    public function testWarmUpGenerateFieldSetsWithImport()
    {
        $fieldName = self::getUniqueFieldName('customer');

        $this->container->loadFromExtension('rollerworks_record_filter', array(
            'fieldsets' => array(
                $fieldName => array(
                    'fields' => array(
                        'id' => array(
                            'type' => array('name' => 'number', 'params' => array()),
                            'required' => false,
                            'accept_ranges' => false,
                            'accept_compares' => false,
                        ),
                    ),
                    'import' => array(
                          array('class' => 'Rollerworks\Bundle\RecordFilterBundle\Tests\Fixtures\BaseBundle\Entity\ECommerce\ECommerceCustomer')
                    ),
                )
            ),

            'factories' => array(
                'fieldset' => array('auto_generate' => true),
            )
        ));

        $this->createTypes();
        $this->container->set('customer_type', new CustomerType());

        $this->compileContainer($this->container);
        $this->cacheWarmer->warmUp($this->cacheDir);

        $this->assertEquals(array($fieldName . '/FieldSet.php'), $this->getFilesInCache());

        $this->assertFieldSetEquals($fieldName, array(
            'id' => array(
                'type' => new FilterTypeConfig('number'),
                'required' => false,
                'accept_ranges' => false,
                'accept_compares' => false
            ),
            'customer_id' => array(
                'type' => new FilterTypeConfig('customer_type'),
                'required' => false,
                'accept_ranges' => false,
                'accept_compares' => false,

                'class' => 'Rollerworks\Bundle\RecordFilterBundle\Tests\Fixtures\BaseBundle\Entity\ECommerce\ECommerceCustomer',
                'property' => 'id',
            ),
        ));
    }

    public function testWarmUpGenerateFieldSetsWithImport2()
    {
        $fieldName = self::getUniqueFieldName('invoice');

        $this->container->loadFromExtension('rollerworks_record_filter', array(
            'fieldsets' => array(
                $fieldName => array(
                    'fields' => array(
                        'id' => array(
                            'type' => array('name' => 'number', 'params' => array()),
                            'required' => false,
                            'accept_ranges' => false,
                            'accept_compares' => false,
                        ),
                    ),
                    'import' => array(
                          array('class' => 'Rollerworks\Bundle\RecordFilterBundle\Tests\Fixtures\BaseBundle\Entity\ECommerce\ECommerceInvoiceRow')
                    ),
                )
            ),

            'factories' => array(
                'fieldset' => array('auto_generate' => true),
            )
        ));

        $this->createTypes();

        $this->container->setAlias('invoice_type', 'rollerworks_record_filter.filter_type.number');
        $this->container->set('status_type', new StatusType());

        $this->compileContainer($this->container);
        $this->cacheWarmer->warmUp($this->cacheDir);

        $this->assertEquals(array($fieldName . '/FieldSet.php'), $this->getFilesInCache());

        $this->assertFieldSetEquals($fieldName, array(
            'id' => array(
                'type' => new FilterTypeConfig('number'),
                'required' => false,
                'accept_ranges' => false,
                'accept_compares' => false
            ),

            'invoice_label' => array(
                'type' => null,
                'required' => false,
                'accept_ranges' => false,
                'accept_compares' => false,

                'class' => 'Rollerworks\Bundle\RecordFilterBundle\Tests\Fixtures\BaseBundle\Entity\ECommerce\ECommerceInvoiceRow',
                'property' => 'label',
            ),
            'invoice_price' => array(
                'type' => new FilterTypeConfig('decimal'),
                'required' => false,
                'accept_ranges' => false,
                'accept_compares' => false,

                'class' => 'Rollerworks\Bundle\RecordFilterBundle\Tests\Fixtures\BaseBundle\Entity\ECommerce\ECommerceInvoiceRow',
                'property' => 'price',
            ),
        ));
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

        $result = $this->getFilesInCache();
        sort($result);

        $this->assertEquals(array(
            'customer/FieldSet.php',
            'customer/SqlWhereBuilder.php',
            'invoice/FieldSet.php',
            'invoice/SqlWhereBuilder.php'
        ), $result);
    }

    protected function assertFieldSetEquals($fieldName, array $fields)
    {
        require $this->container->getParameter('kernel.cache_dir') . '/record_filter/' . $fieldName . '/FieldSet.php';

        $class = 'RecordFilter\\' . $fieldName . '\\FieldSet';

        /** @var FieldSet $actual */
        $actual = new $class($this->container->get('filter_type_factory'), $this->container->get('translator'), 'label.', 'filters');
        $this->assertEquals($fieldName, $actual->getSetName());

        foreach ($fields as $fieldName => $data) {
            $this->assertTrue($actual->has($fieldName), sprintf('FieldSet "%s" has field "%s"', $fieldName, $fieldName));

            $field = $actual->get($fieldName);
            $this->assertEquals('label.' . $fieldName, $field->getLabel());
            $this->assertEquals($data['required'], $field->isRequired());
            $this->assertEquals($data['accept_ranges'], $field->acceptRanges());
            $this->assertEquals($data['accept_compares'], $field->acceptCompares());

            if (isset($data['class'])) {
                $this->assertEquals($data['class'], $field->getPropertyRefClass());
                $this->assertEquals($data['property'], $field->getPropertyRefField());
            }
            else {
                $this->assertNull($field->getPropertyRefClass());
                $this->assertNull($field->getPropertyRefField());
            }

            if (null !== $data['type']) {
                $this->assertFilterTypeEquals($data['type'], $field->getType());
            }
        }
    }

    protected function assertFilterTypeEquals(FilterTypeConfig $expected, FilterType\FilterTypeInterface $actual)
    {
        $this->assertInstanceOf(get_class($this->container->get('filter_type_factory')->newInstance($expected->getName())), $actual);

        if ($expected->hasParams()) {
            $this->assertInstanceOf('Rollerworks\Bundle\RecordFilterBundle\Type\ConfigurableTypeInterface', $actual);
            $this->assertEquals($expected->getParams(), $actual->getOptions());
        }
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
        $annotationReader->addGlobalIgnoredName('Id');
        $annotationReader->addGlobalIgnoredName('Column');
        $annotationReader->addGlobalIgnoredName('GeneratedValue');
        $annotationReader->addGlobalIgnoredName('OneToOne');
        $annotationReader->addGlobalIgnoredName('OneToMany');
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

    private function createTypes()
    {
        $this->container->register('rollerworks_record_filter.filter_type.date','Rollerworks\Bundle\RecordFilterBundle\Type\Date');
        $this->container->register('rollerworks_record_filter.filter_type.time','Rollerworks\Bundle\RecordFilterBundle\Type\Time');
        $this->container->register('rollerworks_record_filter.filter_type.number', 'Rollerworks\Bundle\RecordFilterBundle\Type\Number');
        $this->container->register('rollerworks_record_filter.filter_type.decimal', 'Rollerworks\Bundle\RecordFilterBundle\Type\Decimal');

        $this->container->getDefinition('rollerworks_record_filter.filter_type.date')->setScope('prototype');
        $this->container->getDefinition('rollerworks_record_filter.filter_type.time')->setScope('prototype');
        $this->container->getDefinition('rollerworks_record_filter.filter_type.number')->setScope('prototype');
        $this->container->getDefinition('rollerworks_record_filter.filter_type.decimal')->setScope('prototype');

        $filterTypeFactory = new FilterTypeFactory($this->container, array(
            'date'    => 'rollerworks_record_filter.filter_type.date',
            'time'    => 'rollerworks_record_filter.filter_type.time',
            'number'  => 'rollerworks_record_filter.filter_type.number',
            'decimal' => 'rollerworks_record_filter.filter_type.decimal',
        ));

        $this->container->set('filter_type_factory', $filterTypeFactory);
    }

    private static function getUniqueFieldName($name)
    {
        return $name . str_replace('.', '', microtime(true));
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
