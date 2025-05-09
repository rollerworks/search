<?php

declare(strict_types=1);

/*
 * This file is part of the RollerworksSearch package.
 *
 * (c) Sebastiaan Stok <s.stok@rollerscapes.net>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Rollerworks\Component\Search\Tests\Doctrine\Orm;

use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Rollerworks\Component\Search\Doctrine\Orm\FieldConfigBuilder;
use Rollerworks\Component\Search\Doctrine\Orm\OrmQueryField as QueryField;
use Rollerworks\Component\Search\Extension\Core\Type\IntegerType;
use Rollerworks\Component\Search\Extension\Core\Type\TextType;
use Rollerworks\Component\Search\Searches;
use Rollerworks\Component\Search\SearchFactory;

/**
 * @internal
 */
final class FieldConfigBuilderTest extends TestCase
{
    use ProphecyTrait;

    public const CUSTOMER_CLASS = Fixtures\Entity\ECommerceCustomer::class;
    public const INVOICE_CLASS = Fixtures\Entity\ECommerceInvoice::class;

    /**
     * @var ObjectProphecy
     */
    private $em;

    /**
     * @var SearchFactory
     */
    private $searchFactory;

    protected function setUp(): void
    {
        parent::setUp();

        $this->searchFactory = Searches::createSearchFactoryBuilder()->getSearchFactory();
        $this->em = $this->prophesize('Doctrine\ORM\EntityManagerInterface');
    }

    private function getFieldSet($build = true)
    {
        $fieldSet = $this->searchFactory->createFieldSetBuilder();

        $fieldSet->add('id', IntegerType::class);
        $fieldSet->add('credit_parent', IntegerType::class);

        $fieldSet->add('customer', IntegerType::class);
        $fieldSet->add('customer_name', TextType::class);

        return $build ? $fieldSet->getFieldSet('invoice') : $fieldSet;
    }

    private function getClassMetadata(string $entityClass, array $fields): void
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

    /** @test */
    public function resolve_with_default_entity(): void
    {
        $this->getClassMetadata(self::INVOICE_CLASS, [
            'id' => ['type' => 'integer'],
            'parent' => ['type' => 'integer'],
        ]);

        $this->getClassMetadata(self::CUSTOMER_CLASS, [
            'id' => ['type' => 'integer'],
            'first_name' => ['type' => 'string'],
            'last_name' => ['type' => 'string'],
        ]);

        $fieldConfigBuilder = new FieldConfigBuilder($this->em->reveal(), $fieldSet = $this->getFieldSet());

        $fieldConfigBuilder->setDefaultEntity(self::INVOICE_CLASS, 'I');
        $fieldConfigBuilder->setField('id', 'id', null, null, 'smallint');
        $fieldConfigBuilder->setField('credit_parent#0', 'parent');

        $fieldConfigBuilder->setDefaultEntity(self::CUSTOMER_CLASS, 'C');
        $fieldConfigBuilder->setField('customer', 'id');
        $fieldConfigBuilder->setField('customer_name#first_name', 'first_name');
        $fieldConfigBuilder->setField('customer_name#last_name', 'last_name');

        $fields = $fieldConfigBuilder->getFields();

        // Invoice
        self::assertEquals(new QueryField('id', $fieldSet->get('id'), 'smallint', 'id', 'I', self::INVOICE_CLASS), $fields['id'][null]);
        self::assertEquals(new QueryField('credit_parent#0', $fieldSet->get('credit_parent'), 'integer', 'parent', 'I', self::INVOICE_CLASS), $fields['credit_parent'][0]);

        // Customer
        self::assertEquals(new QueryField('customer', $fieldSet->get('customer'), 'integer', 'id', 'C', self::CUSTOMER_CLASS), $fields['customer'][null]);
        self::assertEquals(new QueryField('customer_name#first_name', $fieldSet->get('customer_name'), 'string', 'first_name', 'C', self::CUSTOMER_CLASS), $fields['customer_name']['first_name']);
        self::assertEquals(new QueryField('customer_name#last_name', $fieldSet->get('customer_name'), 'string', 'last_name', 'C', self::CUSTOMER_CLASS), $fields['customer_name']['last_name']);
    }

    /** @test */
    public function resolve_with_full_field_mapping(): void
    {
        $this->getClassMetadata(self::INVOICE_CLASS, [
            'id' => ['type' => 'integer'],
            'parent' => ['type' => 'integer'],
            'invoice_id' => ['type' => 'integer'],
            'parent_id' => ['type' => 'integer'],
        ]);

        $this->getClassMetadata(self::CUSTOMER_CLASS, [
            'id' => ['type' => 'integer'],
            'first_name' => ['type' => 'string'],
            'last_name' => ['type' => 'string'],
        ]);

        $fieldConfigBuilder = new FieldConfigBuilder($this->em->reveal(), $fieldSet = $this->getFieldSet());
        $fieldConfigBuilder->setField('id', 'id', 'I', self::INVOICE_CLASS, 'smallint');
        $fieldConfigBuilder->setField('credit_parent#0', 'parent', 'I', self::INVOICE_CLASS);

        $fields = $fieldConfigBuilder->getFields();

        // Invoice
        self::assertEquals(new QueryField('id', $fieldSet->get('id'), 'smallint', 'id', 'I', self::INVOICE_CLASS), $fields['id'][null]);
        self::assertEquals(new QueryField('credit_parent#0', $fieldSet->get('credit_parent'), 'integer', 'parent', 'I', self::INVOICE_CLASS), $fields['credit_parent'][0]);
    }

    /** @test */
    public function fails_to_resolve_with_join_association(): void
    {
        $this->getClassMetadata(
            self::INVOICE_CLASS,
            [
                'id' => ['type' => 'integer'],
                'parent' => [
                    'join' => [
                        'class' => self::INVOICE_CLASS,
                        'property' => 'id',
                        'type' => 'integer',
                        'column' => 'invoice_id',
                    ],
                ],
                'parent_id' => ['type' => 'integer'],
            ]
        );

        $this->getClassMetadata(self::CUSTOMER_CLASS, [
            'id' => ['type' => 'integer'],
            'name' => ['type' => 'string'],
        ]);

        $fieldConfigBuilder = new FieldConfigBuilder($this->em->reveal(), $fieldSet = $this->getFieldSet());

        $fieldConfigBuilder->setDefaultEntity(self::INVOICE_CLASS, 'I');
        $fieldConfigBuilder->setField('id', 'id', null, null, 'smallint');

        $this->expectException('RuntimeException');
        $this->expectExceptionMessage('Entity field "' . self::INVOICE_CLASS . '"#parent is a JOIN association');

        $fieldConfigBuilder->setField('credit_parent', 'parent', 'I', null, 'parent_id');
    }

    /** @test */
    public function fails_to_resolve_with_multi_column_join_association(): void
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
        $fieldConfigBuilder->setField('id', 'id', 'I', self::INVOICE_CLASS, 'smallint');

        $this->expectException('RuntimeException');
        $this->expectExceptionMessage('Entity field "' . self::INVOICE_CLASS . '"#parent is a JOIN association');

        $fieldConfigBuilder->setField('credit_parent#0', 'parent', 'I', self::INVOICE_CLASS);
    }
}
