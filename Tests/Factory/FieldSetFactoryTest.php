<?php

/**
 * This file is part of the RollerworksRecordFilterBundle.
 *
 * (c) Sebastiaan Stok <s.stok@rollerscapes.net>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Rollerworks\Bundle\RecordFilterBundle\Tests\Factory;

use Rollerworks\Bundle\RecordFilterBundle\Mapping\FilterTypeConfig;
use Rollerworks\Bundle\RecordFilterBundle\FilterField;
use Rollerworks\Bundle\RecordFilterBundle\FieldSet;
use Rollerworks\Bundle\RecordFilterBundle\Factory\FilterTypeFactory;
use Rollerworks\Bundle\RecordFilterBundle\Factory\FieldSetFactory;
use Rollerworks\Bundle\RecordFilterBundle\Type as FilterType;
use Rollerworks\Bundle\RecordFilterBundle\Tests\TestCase;

class FieldSetFactoryTest extends TestCase
{
    /**
     * @var FieldSetFactory
     */
    protected $factory;

    /**
     * @var FilterTypeFactory
     */
    protected $filterTypeFactory;

    protected function setUp()
    {
        parent::setUp();

        $cacheDir = __DIR__ . '/../.cache/record_filter';

        if (!is_dir($cacheDir) && !mkdir($cacheDir, 0777, true)) {
            throw new \RuntimeException('Was unable to create the sub-dir for the RecordFilter::FieldSetFactory.');
        }

        $container = $this->createContainer();
        $container->register('rollerworks_record_filter.filter_type.date','Rollerworks\Bundle\RecordFilterBundle\Type\Date');
        $container->register('rollerworks_record_filter.filter_type.time','Rollerworks\Bundle\RecordFilterBundle\Type\Time');
        $container->register('rollerworks_record_filter.filter_type.number', 'Rollerworks\Bundle\RecordFilterBundle\Type\Number');

        $container->getDefinition('rollerworks_record_filter.filter_type.date')->setScope('prototype');
        $container->getDefinition('rollerworks_record_filter.filter_type.time')->setScope('prototype');
        $container->getDefinition('rollerworks_record_filter.filter_type.number')->setScope('prototype');
        $container->compile();

        $this->filterTypeFactory = new FilterTypeFactory($container, array(
            'date'    => 'rollerworks_record_filter.filter_type.date',
            'time'    => 'rollerworks_record_filter.filter_type.time',
            'number'  => 'rollerworks_record_filter.filter_type.number',
            'invoice' => 'rollerworks_record_filter.filter_type.number',
        ));

        $this->translator->addResource('array', array(
            'invoice_id' => 'invoice label',
            'invoice_date' => 'invoice date',
            'order_id' => 'order number'
        ), 'en', 'filter');

        $this->factory = new FieldSetFactory(__DIR__ . '/../.cache/record_filter', 'RecordFilter', true);
        $this->factory->setTranslator($this->translator);
        $this->factory->setTypesFactory($this->filterTypeFactory);
    }

    protected function tearDown()
    {
        parent::tearDown();

        $cacheDir = realpath(__DIR__ . '/../.cache/record_filter');
        if (!file_exists($cacheDir)) {
            return;
        }

        foreach (new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($cacheDir), \RecursiveIteratorIterator::CHILD_FIRST) as $path) {
            if ($path->isDir()) {
                rmdir($path->__toString());
            } else {
                unlink($path->__toString());
            }
        }
    }

    /**
     * @param FieldSet[] $fieldSets
     *
     * @dataProvider provideFieldSets
     */
    public function testGenerateFieldSets($fieldSets)
    {
        $this->factory->generateClasses($fieldSets);

        foreach ($fieldSets as $fieldSet) {
            $concreteFieldSet = $this->factory->getFieldSet($fieldSet->getSetName());

            $this->assertFieldSetEquals($fieldSet, $concreteFieldSet);
        }
    }

    public function testGenerateFieldSetsInvalidName()
    {
        $this->setExpectedException('InvalidArgumentException', 'FieldSet must have a unique-name.');
        $this->factory->generateClasses(array(FieldSet::create(null)));
    }

    public static function provideFieldSets()
    {
        return array(
            array(
                // List of FieldSet's
                array(
                    FieldSet::create('invoice')
                        ->set('invoice_id', FilterField::create('invoice label', new FilterTypeConfig('invoice')))
                        ->set('invoice_date', FilterField::create('invoice date', new FilterTypeConfig('date')))
                        ->set('invoice_price', FilterField::create('invoice_price', new FilterTypeConfig('number')))
                        ->set('invoice_customer', FilterField::create('invoice_customer')),
                    FieldSet::create('customer')
                        ->set('customer_id', FilterField::create('customer_id', new FilterTypeConfig('number', array('max' => null, 'min' => '0'))))
                ),
            ),

            array(
                // List of FieldSet's
                array(
                    FieldSet::create('order')
                        ->set('order_id', FilterField::create('order number', new FilterTypeConfig('number'))->setPropertyRef('ECommerce:Order', 'id'))
                        ->set('order_customer', FilterField::create('order_customer')),
                ),
            )
        );
    }

    protected function assertFieldSetEquals(FieldSet $expected, FieldSet $actual)
    {
        $this->assertEquals($expected->getSetName(), $actual->getSetName());

        foreach ($expected->all() as $fieldName => $field) {
            $this->assertTrue($actual->has($fieldName), sprintf('FieldSet "%s" has field "%s"', $expected->getSetName(), $fieldName));
            $this->assertEquals($field->getLabel(), $actual->get($fieldName)->getLabel());

            $actualField = $actual->get($fieldName);

            if (null === $field->getType()) {
                $this->assertNull($actualField->getType());
            } else {
                $this->assertFilterTypeEquals($field->getType(), $actualField->getType());
            }

            if (null !== $field->getPropertyRefClass()) {
                $this->assertEquals($field->getPropertyRefClass(), $actualField->getPropertyRefClass());
                $this->assertEquals($field->getPropertyRefField(), $actualField->getPropertyRefField());
            }
        }
    }

    protected function assertFilterTypeEquals(FilterTypeConfig $expected, FilterType\FilterTypeInterface $actual)
    {
        $this->assertInstanceOf(get_class($this->filterTypeFactory->newInstance($expected->getName())), $actual);

        if ($expected->hasParams()) {
            $this->assertInstanceOf('Rollerworks\Bundle\RecordFilterBundle\Type\ConfigurableTypeInterface', $actual);
            $this->assertEquals($expected->getParams(), $actual->getOptions());
        }
    }
}
