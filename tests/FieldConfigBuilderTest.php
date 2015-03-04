<?php

/*
 * This file is part of the RollerworksSearch Component package.
 *
 * (c) Sebastiaan Stok <s.stok@rollerscapes.net>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Rollerworks\Component\Search\Tests\Doctrine\Orm;

use Prophecy\Prophecy\ObjectProphecy;
use Rollerworks\Component\Search\Doctrine\Orm\FieldConfigBuilder;
use Rollerworks\Component\Search\Searches;
use Rollerworks\Component\Search\SearchFactory;

final class FieldConfigBuilderTest extends \PHPUnit_Framework_TestCase
{
    const CUSTOMER_CLASS = 'Rollerworks\Component\Search\Tests\Doctrine\Orm\Fixtures\Entity\ECommerceCustomer';
    const INVOICE_CLASS = 'Rollerworks\Component\Search\Tests\Doctrine\Orm\Fixtures\Entity\ECommerceInvoice';
    const INVOICE_CLASS2 = 'Rollerworks\Component\Search\Tests\Doctrine\Orm\Fixtures\Entity\ECommerceInvoice2';

    /**
     * @var ObjectProphecy
     */
    private $em;

    /**
     * @var SearchFactory
     */
    private $searchFactory;

    protected function setUp()
    {
        parent::setUp();

        $this->searchFactory = Searches::createSearchFactoryBuilder()->getSearchFactory();
        $this->em = $this->prophesize('Doctrine\ORM\EntityManagerInterface');
    }

    private function getFieldSet($build = true)
    {
        $fieldSet = $this->searchFactory->createFieldSetBuilder('invoice');

        $fieldSet->add('id', 'integer', [], false, self::INVOICE_CLASS, 'id');
        $fieldSet->add('credit_parent', 'integer', [], false, self::INVOICE_CLASS, 'parent');

        $fieldSet->add('customer', 'integer', [], false, self::CUSTOMER_CLASS, 'id');
        $fieldSet->add('customer_name', 'text', [], false, self::CUSTOMER_CLASS, 'name');

        return $build ? $fieldSet->getFieldSet() : $fieldSet;
    }

    /**
     * @param string $entityClass
     * @param array  $fields
     */
    private function getClassMetadata($entityClass, array $fields)
    {
        $classMetadata = $this->prophesize('Doctrine\ORM\Mapping\ClassMetadata');
        $classMetadata->getName()->willReturn($entityClass);

        foreach ($fields as $property => $field) {
            if (isset($field['join'])) {
                $classMetadata->isAssociationWithSingleJoinColumn($property)->willReturn(true);
                $classMetadata->hasAssociation($property)->willReturn(true);
                $classMetadata->getTypeOfField($property)->willReturn(null);
                $classMetadata->getAssociationTargetClass($property)->willReturn($field['join']['class']);
                $classMetadata->getSingleAssociationReferencedJoinColumnName($property)->willReturn(
                    $field['join']['column']
                );

                $joinClassMetadata = $this->prophesize('Doctrine\ORM\Mapping\ClassMetadata');
                $joinClassMetadata->getName()->willReturn($field['join']['class']);
                $joinClassMetadata->getFieldForColumn($field['join']['column'])->willReturn($field['join']['property']);
                $joinClassMetadata->getTypeOfField($field['join']['property'])->willReturn($field['join']['type']);

                $this->em->getClassMetadata($field['join']['class'])->willReturn($joinClassMetadata->reveal());
            } elseif (isset($field['join_multi'])) {
                $classMetadata->isAssociationWithSingleJoinColumn($property)->willReturn(false);
                $classMetadata->hasAssociation($property)->willReturn(true);
                $classMetadata->getTypeOfField($property)->willReturn(null);
            } else {
                $classMetadata->isAssociationWithSingleJoinColumn($property)->willReturn(false);
                $classMetadata->hasAssociation($property)->willReturn(false);
                $classMetadata->getTypeOfField($property)->willReturn($field['type']);
            }
        }

        $this->em->getClassMetadata($entityClass)->willReturn($classMetadata->reveal());
    }

    public function testResolveWithEntityMapping()
    {
        $this->getClassMetadata(self::INVOICE_CLASS, [
            'id' => ['type' => 'integer'],
            'parent' => ['type' => 'integer'],
        ]);

        $this->getClassMetadata(self::CUSTOMER_CLASS, [
            'id' => ['type' => 'integer'],
            'name' => ['type' => 'string'],
        ]);

        $fieldConfigBuilder = new FieldConfigBuilder($this->em->reveal(), $this->getFieldSet());
        $fieldConfigBuilder->setEntityMapping(self::INVOICE_CLASS, 'I');
        $fieldConfigBuilder->setEntityMapping(self::CUSTOMER_CLASS, 'C');

        $fields = $fieldConfigBuilder->getFields();

        $this->assertArrayHasKey('id', $fields);
        $this->assertArrayHasKey('customer_name', $fields);

        // Alias
        $this->assertEquals('I', $fields['id']->getAlias());
        $this->assertEquals('C', $fields['customer_name']->getAlias());

        // Column
        $this->assertEquals('id', $fields['id']->getColumn(false));
        $this->assertEquals('name', $fields['customer_name']->getColumn(false));

        // Type
        $this->assertEquals('integer', $fields['id']->getDbType()->getName());
        $this->assertEquals('string', $fields['customer_name']->getDbType()->getName());
    }

    public function testResolveWithFieldMapping()
    {
        $this->getClassMetadata(self::INVOICE_CLASS, [
            'id' => ['type' => 'integer'],
            'parent' => ['type' => 'integer'],
        ]);

        $this->getClassMetadata(self::CUSTOMER_CLASS, [
            'id' => ['type' => 'integer'],
            'name' => ['type' => 'string'],
        ]);

        $fieldConfigBuilder = new FieldConfigBuilder($this->em->reveal(), $this->getFieldSet());
        $fieldConfigBuilder->setField('id', 'I');

        $fields = $fieldConfigBuilder->getFields();

        $this->assertArrayHasKey('id', $fields);
        $this->assertArrayNotHasKey('parent', $fields);
        $this->assertArrayNotHasKey('customer_name', $fields);

        $this->assertEquals('I', $fields['id']->getAlias());
        $this->assertEquals('id', $fields['id']->getColumn(false));
        $this->assertEquals('integer', $fields['id']->getDbType()->getName());
    }

    public function testResolveWithFullFieldMapping()
    {
        $this->getClassMetadata(self::INVOICE_CLASS, [
            'id' => ['type' => 'integer'],
            'parent' => ['type' => 'integer'],
            'invoice_id' => ['type' => 'integer'],
            'parent_id' => ['type' => 'integer'],
        ]);

        $this->getClassMetadata(self::CUSTOMER_CLASS, [
            'id' => ['type' => 'integer'],
            'name' => ['type' => 'string'],
        ]);

        $fieldConfigBuilder = new FieldConfigBuilder($this->em->reveal(), $this->getFieldSet());
        $fieldConfigBuilder->setField('id', 'I', self::INVOICE_CLASS, 'invoice_id', 'string');
        $fieldConfigBuilder->setField('credit_parent', 'I', null, 'parent_id', 'integer');

        $fields = $fieldConfigBuilder->getFields();

        $this->assertArrayHasKey('id', $fields);
        $this->assertEquals('I', $fields['id']->getAlias());
        $this->assertEquals('invoice_id', $fields['id']->getColumn(false));
        $this->assertEquals('string', $fields['id']->getDbType()->getName());

        $this->assertArrayHasKey('credit_parent', $fields);
        $this->assertEquals('I', $fields['credit_parent']->getAlias());
        $this->assertEquals('parent_id', $fields['credit_parent']->getColumn(false));
        $this->assertEquals('integer', $fields['credit_parent']->getDbType()->getName());
    }

    public function testResolveWithEntityMappingAndFieldMapping()
    {
        $this->getClassMetadata(self::INVOICE_CLASS, [
            'id' => ['type' => 'integer'],
            'parent' => ['type' => 'integer'],
        ]);

        $this->getClassMetadata(self::CUSTOMER_CLASS, [
            'id' => ['type' => 'integer'],
            'name' => ['type' => 'string'],
        ]);

        $fieldConfigBuilder = new FieldConfigBuilder($this->em->reveal(), $this->getFieldSet());
        $fieldConfigBuilder->setEntityMapping(self::CUSTOMER_CLASS, 'C');
        $fieldConfigBuilder->setField('id', 'I');

        $fields = $fieldConfigBuilder->getFields();

        $this->assertArrayHasKey('id', $fields);
        $this->assertArrayHasKey('customer', $fields);
        $this->assertArrayHasKey('customer_name', $fields);
        $this->assertArrayNotHasKey('credit_parent', $fields);

        // Alias
        $this->assertEquals('I', $fields['id']->getAlias());
        $this->assertEquals('C', $fields['customer_name']->getAlias());

        // Column
        $this->assertEquals('id', $fields['id']->getColumn(false));
        $this->assertEquals('name', $fields['customer_name']->getColumn(false));

        // Type
        $this->assertEquals('integer', $fields['id']->getDbType()->getName());
        $this->assertEquals('string', $fields['customer_name']->getDbType()->getName());
    }

    public function testResolveWithEntityMappingAnExplicitFieldMapping()
    {
        $this->getClassMetadata(self::INVOICE_CLASS, [
            'id' => ['type' => 'integer'],
            'parent' => ['type' => 'integer'],
        ]);

        $this->getClassMetadata(self::CUSTOMER_CLASS, [
            'id' => ['type' => 'integer'],
            'name' => ['type' => 'string'],
        ]);

        $fieldConfigBuilder = new FieldConfigBuilder($this->em->reveal(), $this->getFieldSet());
        $fieldConfigBuilder->setEntityMapping(self::INVOICE_CLASS, 'I');
        $fieldConfigBuilder->setEntityMapping(self::CUSTOMER_CLASS, 'C');
        $fieldConfigBuilder->setField('credit_parent', 'PI');

        $fields = $fieldConfigBuilder->getFields();

        $this->assertArrayHasKey('id', $fields);
        $this->assertArrayHasKey('customer_name', $fields);
        $this->assertArrayHasKey('credit_parent', $fields);

        // Alias
        $this->assertEquals('I', $fields['id']->getAlias());
        $this->assertEquals('C', $fields['customer_name']->getAlias());
        $this->assertEquals('PI', $fields['credit_parent']->getAlias());

        // Column
        $this->assertEquals('id', $fields['id']->getColumn(false));
        $this->assertEquals('name', $fields['customer_name']->getColumn(false));
        $this->assertEquals('parent', $fields['credit_parent']->getColumn(false));

        // Type
        $this->assertEquals('integer', $fields['id']->getDbType()->getName());
        $this->assertEquals('string', $fields['customer_name']->getDbType()->getName());
        $this->assertEquals('integer', $fields['credit_parent']->getDbType()->getName());
    }

    public function testResolveWithSingleJoinAssociation()
    {
        $this->getClassMetadata(
            self::INVOICE_CLASS,
            [
                'id' => ['type' => 'integer'],
                'parent' => [
                    'join' => [
                        'class' => self::INVOICE_CLASS2,
                        'column' => 'invoice_id',
                        'property' => 'id',
                        'type' => 'integer',
                    ],
                ],
            ]
        );

        $this->getClassMetadata(self::CUSTOMER_CLASS, [
            'id' => ['type' => 'integer'],
            'name' => ['type' => 'string'],
        ]);

        $fieldConfigBuilder = new FieldConfigBuilder($this->em->reveal(), $this->getFieldSet());
        $fieldConfigBuilder->setEntityMapping(self::INVOICE_CLASS, 'I');
        $fieldConfigBuilder->setEntityMapping(self::INVOICE_CLASS2, 'P');
        $fieldConfigBuilder->setEntityMapping(self::CUSTOMER_CLASS, 'C');

        $fields = $fieldConfigBuilder->getFields();

        $this->assertArrayHasKey('id', $fields);
        $this->assertArrayHasKey('customer_name', $fields);

        // Alias
        $this->assertEquals('I', $fields['id']->getAlias());
        $this->assertEquals('C', $fields['customer_name']->getAlias());

        // Column
        $this->assertEquals('id', $fields['id']->getColumn(false));
        $this->assertEquals('name', $fields['customer_name']->getColumn(false));

        // Type
        $this->assertEquals('integer', $fields['id']->getDbType()->getName());
        $this->assertEquals('string', $fields['customer_name']->getDbType()->getName());
    }

    public function testFailureResolveWithMultiColumnJoinAssociationAndNoFieldMapping()
    {
        $this->getClassMetadata(
            self::INVOICE_CLASS,
            [
                'id' => ['type' => 'integer'],
                'parent' => [
                    'join_multi' => true,
                ],
            ]
        );

        $this->getClassMetadata(self::CUSTOMER_CLASS, [
            'id' => ['type' => 'integer'],
            'name' => ['type' => 'string'],
        ]);

        $fieldConfigBuilder = new FieldConfigBuilder($this->em->reveal(), $this->getFieldSet());
        $fieldConfigBuilder->setEntityMapping(self::INVOICE_CLASS, 'I');
        $fieldConfigBuilder->setEntityMapping(self::CUSTOMER_CLASS, 'C');

        $this->setExpectedException('RuntimeException', '#parent is a JOIN association with multiple columns');
        $fieldConfigBuilder->getFields();
    }

    public function testFailureResolveWithExplicitFieldMappingToJoinAssociation()
    {
        $this->getClassMetadata(
            self::INVOICE_CLASS,
            [
                'id' => ['type' => 'integer'],
                'parent' => [
                    'join_multi' => true,
                ],
                'parent_id' => [
                    'join' => [
                        'class' => self::INVOICE_CLASS2,
                        'column' => 'invoice_id',
                        'property' => 'id',
                        'type' => 'integer',
                    ],
                ],
            ]
        );

        $fieldConfigBuilder = new FieldConfigBuilder($this->em->reveal(), $this->getFieldSet());
        $fieldConfigBuilder->setField('credit_parent', 'I', null, 'parent_id');

        $this->setExpectedException('RuntimeException', 'Search field "credit_parent" is explicitly mapped');
        $fieldConfigBuilder->getFields();
    }

    public function testResolveWithMultiColumnJoinAssociationAndFieldMapping()
    {
        $this->getClassMetadata(
            self::INVOICE_CLASS,
            [
                'id' => ['type' => 'integer'],
                'parent' => [
                    'join_multi' => true,
                ],
                'parent_id' => ['type' => 'integer'],
            ]
        );

        $this->getClassMetadata(self::CUSTOMER_CLASS, [
            'id' => ['type' => 'integer'],
            'name' => ['type' => 'string'],
        ]);

        $fieldConfigBuilder = new FieldConfigBuilder($this->em->reveal(), $this->getFieldSet());
        $fieldConfigBuilder->setEntityMapping(self::INVOICE_CLASS, 'I');
        $fieldConfigBuilder->setEntityMapping(self::CUSTOMER_CLASS, 'C');
        $fieldConfigBuilder->setField('credit_parent', 'PI', null, 'parent_id');

        $fields = $fieldConfigBuilder->getFields();

        $this->assertArrayHasKey('id', $fields);
        $this->assertArrayHasKey('customer_name', $fields);

        // Alias
        $this->assertEquals('I', $fields['id']->getAlias());
        $this->assertEquals('C', $fields['customer_name']->getAlias());
        $this->assertEquals('PI', $fields['credit_parent']->getAlias());

        // Column
        $this->assertEquals('id', $fields['id']->getColumn(false));
        $this->assertEquals('name', $fields['customer_name']->getColumn(false));
        $this->assertEquals('parent_id', $fields['credit_parent']->getColumn(false));

        // Type
        $this->assertEquals('integer', $fields['id']->getDbType()->getName());
        $this->assertEquals('string', $fields['customer_name']->getDbType()->getName());
        $this->assertEquals('integer', $fields['credit_parent']->getDbType()->getName());
    }

    public function testResolveWithFieldsNoModelIsIgnored()
    {
        $this->getClassMetadata(self::INVOICE_CLASS, [
            'id' => ['type' => 'integer'],
            'parent' => ['type' => 'integer'],
        ]);

        $this->getClassMetadata(self::CUSTOMER_CLASS, [
            'id' => ['type' => 'integer'],
            'name' => ['type' => 'string'],
        ]);

        $fieldSet = $this->getFieldSet(false);
        $fieldSet->add('customer_birthday', 'date');

        $fieldConfigBuilder = new FieldConfigBuilder($this->em->reveal(), $fieldSet->getFieldSet());
        $fieldConfigBuilder->setEntityMapping(self::INVOICE_CLASS, 'I');
        $fieldConfigBuilder->setEntityMapping(self::CUSTOMER_CLASS, 'C');

        $fields = $fieldConfigBuilder->getFields();

        $this->assertArrayHasKey('id', $fields);
        $this->assertArrayHasKey('customer_name', $fields);
        $this->assertArrayNotHasKey('customer_birthday', $fields);
    }
}
