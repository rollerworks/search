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

namespace Rollerworks\Bundle\SearchBundle\Tests;

use BadMethodCallException;
use LogicException;
use PHPUnit\Framework\TestCase;
use Rollerworks\Component\Search\Extension\Core\Type\IntegerType;
use Rollerworks\Component\Search\Extension\Core\Type\TextType;
use Rollerworks\Component\Search\Field\OrderFieldType;
use Rollerworks\Component\Search\FieldSet;
use Rollerworks\Component\Search\SearchCondition;
use Rollerworks\Component\Search\SearchConditionBuilder;
use Rollerworks\Component\Search\Searches;
use Rollerworks\Component\Search\SearchOrder;
use Rollerworks\Component\Search\SearchPrimaryCondition;
use Rollerworks\Component\Search\Tests\Mock\FieldSetStub;
use Rollerworks\Component\Search\Value\ValuesBag;
use Rollerworks\Component\Search\Value\ValuesGroup;

/**
 * @internal
 */
final class SearchConditionBuilderTest extends TestCase
{
    /** @test */
    public function it_produces_an_empty_condition(): void
    {
        $fieldSet = new FieldSetStub();

        $builder = SearchConditionBuilder::create($fieldSet);
        self::assertEquals(new SearchCondition($fieldSet, new ValuesGroup()), $builder->getSearchCondition());

        $builder = SearchConditionBuilder::create($fieldSet, ValuesGroup::GROUP_LOGICAL_OR);
        self::assertEquals(new SearchCondition($fieldSet, new ValuesGroup(ValuesGroup::GROUP_LOGICAL_OR)), $builder->getSearchCondition());
    }

    /** @test */
    public function it_disallows_serialization(): void
    {
        $fieldSet = new FieldSetStub();
        $builder = SearchConditionBuilder::create($fieldSet);

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('Unable serialize a SearchConditionBuilder. Call getSearchCondition() and serialize the SearchCondition itself.');

        serialize($builder);
    }

    /** @test */
    public function it_sorting_at_nested_levels(): void
    {
        $searchFactory = Searches::createSearchFactory();

        $fieldSetBuilder = $searchFactory->createFieldSetBuilder();
        $fieldSetBuilder
            ->add('id', IntegerType::class)
            ->add('@id', OrderFieldType::class)
        ;
        $fieldSet = $fieldSetBuilder->getFieldSet('users');
        $condBuilder = SearchConditionBuilder::create($fieldSet);

        $this->expectException(BadMethodCallException::class);
        $this->expectExceptionMessage('Cannot add ordering at nested levels.');

        $condBuilder->group()
            ->order('@id')
        ;
    }

    /**
     * @test
     *
     * @dataProvider provideBuilderExpectations
     */
    public function it_produces_a_condition_with_expected_structure(callable $conditionProvider, callable $builder): void
    {
        $searchFactory = Searches::createSearchFactory();

        $fieldSetBuilder = $searchFactory->createFieldSetBuilder();
        $fieldSetBuilder
            ->add('id', IntegerType::class)
            ->add('fist-name', TextType::class)
            ->add('last-name', TextType::class)

            ->add('_user', IntegerType::class)
            ->add('@id', OrderFieldType::class)
            ->add('@date', OrderFieldType::class)
        ;
        $fieldSet = $fieldSetBuilder->getFieldSet('users');
        $condBuilder = SearchConditionBuilder::create($fieldSet);

        $builder($condBuilder);

        self::assertEquals($conditionProvider($fieldSet), $condBuilder->getSearchCondition());
    }

    public function provideBuilderExpectations(): iterable
    {
        yield 'root (AND)' => [
            static function (FieldSet $fieldSet): SearchCondition {
                $idValues = (new ValuesBag())
                    ->addSimpleValue(10)
                    ->addSimpleValue(30)
                ;

                $root = new ValuesGroup();
                $root->addField('id', $idValues);

                return new SearchCondition($fieldSet, $root);
            },
            static function (SearchConditionBuilder $builder): void {
                $builder
                    ->field('id')
                        ->addSimpleValue(10)
                        ->addSimpleValue(30)
                    ->end()
                ;
            },
        ];

        yield 'root (OR)' => [
            static function (FieldSet $fieldSet): SearchCondition {
                $idValues = (new ValuesBag())
                    ->addSimpleValue(10)
                    ->addSimpleValue(30)
                ;

                $root = new ValuesGroup(ValuesGroup::GROUP_LOGICAL_OR);
                $root->addField('id', $idValues);

                return new SearchCondition($fieldSet, $root);
            },
            static function (SearchConditionBuilder $builder): void {
                $builder
                    ->setGroupLogical(ValuesGroup::GROUP_LOGICAL_OR)
                    ->field('id')
                        ->addSimpleValue(10)
                        ->addSimpleValue(30)
                    ->end()
                ;
            },
        ];

        yield 'nested group (AND)' => [
            static function (FieldSet $fieldSet): SearchCondition {
                $idValues = (new ValuesBag())
                    ->addSimpleValue(10)
                    ->addSimpleValue(30)
                ;

                $root = new ValuesGroup();
                $root->addGroup(
                    (new ValuesGroup())
                        ->addField('id', $idValues)
                );

                return new SearchCondition($fieldSet, $root);
            },
            static function (SearchConditionBuilder $builder): void {
                $builder
                    ->group()
                        ->field('id')
                            ->addSimpleValue(10)
                            ->addSimpleValue(30)
                        ->end()
                    ->end()
                ;
            },
        ];

        yield 'nested group (OR)' => [
            static function (FieldSet $fieldSet): SearchCondition {
                $idValues = (new ValuesBag())
                    ->addSimpleValue(10)
                    ->addSimpleValue(30)
                ;

                $root = new ValuesGroup();
                $root->addGroup(
                    (new ValuesGroup(ValuesGroup::GROUP_LOGICAL_OR))
                        ->addField('id', $idValues)
                );

                return new SearchCondition($fieldSet, $root);
            },
            static function (SearchConditionBuilder $builder): void {
                $builder
                    ->group(ValuesGroup::GROUP_LOGICAL_OR)
                        ->field('id')
                            ->addSimpleValue(10)
                            ->addSimpleValue(30)
                        ->end()
                    ->end()
                ;
            },
        ];

        yield 'expend field' => [
            static function (FieldSet $fieldSet): SearchCondition {
                $idValues = (new ValuesBag())
                    ->addSimpleValue(10)
                    ->addSimpleValue(30)
                    ->addSimpleValue(20)
                    ->addSimpleValue(50)
                ;

                $root = new ValuesGroup();
                $root->addField('id', $idValues);

                return new SearchCondition($fieldSet, $root);
            },
            static function (SearchConditionBuilder $builder): void {
                $builder
                    ->field('id')
                        ->addSimpleValue(10)
                        ->addSimpleValue(30)
                    ->end()
                    ->field('id')
                        ->addSimpleValue(20)
                        ->addSimpleValue(50)
                    ->end()
                ;
            },
        ];

        yield 'overwrite field' => [
            static function (FieldSet $fieldSet): SearchCondition {
                $idValues = (new ValuesBag())
                    ->addSimpleValue(20)
                    ->addSimpleValue(50)
                ;

                $root = new ValuesGroup();
                $root->addField('id', $idValues);

                return new SearchCondition($fieldSet, $root);
            },
            static function (SearchConditionBuilder $builder): void {
                $builder
                    ->field('id')
                        ->addSimpleValue(10)
                        ->addSimpleValue(30)
                    ->end()
                    ->overwriteField('id')
                        ->addSimpleValue(20)
                        ->addSimpleValue(50)
                    ->end()
                ;
            },
        ];

        yield 'result order' => [
            static function (FieldSet $fieldSet): SearchCondition {
                $idValues = (new ValuesBag())
                    ->addSimpleValue(10)
                    ->addSimpleValue(30)
                ;

                $root = new ValuesGroup();
                $root->addField('id', $idValues);

                $sortGroup = new ValuesGroup();
                $sortGroup->addField('@id', (new ValuesBag())->addSimpleValue('DESC'));
                $sortGroup->addField('@date', (new ValuesBag())->addSimpleValue('ASC'));

                $condition = new SearchCondition($fieldSet, $root);
                $condition->setOrder(new SearchOrder($sortGroup));

                return $condition;
            },
            static function (SearchConditionBuilder $builder): void {
                $builder
                    ->field('id')
                        ->addSimpleValue(10)
                        ->addSimpleValue(30)
                    ->end()
                    ->order('@id', 'DESC')
                    ->order('@date')
                ;
            },
        ];

        yield 'reset previous orders' => [
            static function (FieldSet $fieldSet): SearchCondition {
                $idValues = (new ValuesBag())
                    ->addSimpleValue(10)
                    ->addSimpleValue(30)
                ;

                $root = new ValuesGroup();
                $root->addField('id', $idValues);

                $sortGroup = new ValuesGroup();
                $sortGroup->addField('@date', (new ValuesBag())->addSimpleValue('ASC'));

                $condition = new SearchCondition($fieldSet, $root);
                $condition->setOrder(new SearchOrder($sortGroup));

                return $condition;
            },
            static function (SearchConditionBuilder $builder): void {
                $builder
                    ->field('id')
                        ->addSimpleValue(10)
                        ->addSimpleValue(30)
                    ->end()
                    ->order('@id', 'DESC')
                    ->clearOrder()
                    ->order('@date')
                ;
            },
        ];

        yield 'only primary-condition' => [
            static function (FieldSet $fieldSet): SearchCondition {
                $idValuesPrimary = (new ValuesBag())
                    ->addSimpleValue(10)
                    ->addSimpleValue(30)
                ;

                $rootPrimary = new ValuesGroup();
                $rootPrimary->addField('id', $idValuesPrimary);

                $searchCondition = new SearchCondition($fieldSet, new ValuesGroup());
                $searchCondition->setPrimaryCondition(new SearchPrimaryCondition($rootPrimary));

                return $searchCondition;
            },
            static function (SearchConditionBuilder $builder): void {
                $builder
                    ->primaryCondition()
                        ->field('id')
                            ->addSimpleValue(10)
                            ->addSimpleValue(30)
                        ->end()
                    ->end()
                ;
            },
        ];

        yield 'with primary-condition' => [
            static function (FieldSet $fieldSet): SearchCondition {
                $primaryIdValues = (new ValuesBag())
                    ->addSimpleValue(10)
                    ->addSimpleValue(30)
                ;
                $primaryRoot = new ValuesGroup();
                $primaryRoot->addField('id', $primaryIdValues);

                $idValues = (new ValuesBag())
                    ->addSimpleValue(20)
                    ->addSimpleValue(50)
                ;
                $root = new ValuesGroup();
                $root->addField('id', $idValues);

                $searchCondition = new SearchCondition($fieldSet, $root);
                $searchCondition->setPrimaryCondition(new SearchPrimaryCondition($primaryRoot));

                return $searchCondition;
            },
            static function (SearchConditionBuilder $builder): void {
                $builder
                    ->primaryCondition()
                        ->field('id')
                            ->addSimpleValue(10)
                            ->addSimpleValue(30)
                        ->end()
                    ->end()
                    ->field('id')
                        ->addSimpleValue(20)
                        ->addSimpleValue(50)
                    ->end()
                ;
            },
        ];

        yield 'with primary-condition (sorting)' => [
            static function (FieldSet $fieldSet): SearchCondition {
                $primaryIdValues = (new ValuesBag())
                    ->addSimpleValue(10)
                    ->addSimpleValue(30)
                ;
                $primaryRoot = new ValuesGroup();
                $primaryRoot->addField('id', $primaryIdValues);

                $idValues = (new ValuesBag())
                    ->addSimpleValue(20)
                    ->addSimpleValue(50)
                ;
                $root = new ValuesGroup();
                $root->addField('id', $idValues);

                $primaryCondition = new SearchPrimaryCondition($primaryRoot);
                $primaryCondition->setOrder(
                    new SearchOrder((new ValuesGroup())
                        ->addField('@id', (new ValuesBag())
                            ->addSimpleValue('DESC')))
                );

                $searchCondition = new SearchCondition($fieldSet, $root);
                $searchCondition->setPrimaryCondition($primaryCondition);

                return $searchCondition;
            },
            static function (SearchConditionBuilder $builder): void {
                $builder
                    ->primaryCondition()
                        ->order('@id', 'DESC')
                        ->field('id')
                            ->addSimpleValue(10)
                            ->addSimpleValue(30)
                        ->end()
                    ->end()
                    ->field('id')
                        ->addSimpleValue(20)
                        ->addSimpleValue(50)
                    ->end()
                ;
            },
        ];

        yield 'with primary-condition (private field)' => [
            static function (FieldSet $fieldSet): SearchCondition {
                $primaryIdValues = (new ValuesBag())
                    ->addSimpleValue(10)
                    ->addSimpleValue(30)
                ;
                $primaryRoot = new ValuesGroup();
                $primaryRoot->addField('_user', $primaryIdValues);

                $idValues = (new ValuesBag())
                    ->addSimpleValue(20)
                    ->addSimpleValue(50)
                ;
                $root = new ValuesGroup();
                $root->addField('id', $idValues);

                $searchCondition = new SearchCondition($fieldSet, $root);
                $searchCondition->setPrimaryCondition(new SearchPrimaryCondition($primaryRoot));

                return $searchCondition;
            },
            static function (SearchConditionBuilder $builder): void {
                $builder
                    ->primaryCondition()
                        ->field('_user')
                            ->addSimpleValue(10)
                            ->addSimpleValue(30)
                        ->end()
                    ->end()
                    ->field('id')
                        ->addSimpleValue(20)
                        ->addSimpleValue(50)
                    ->end()
                ;
            },
        ];
    }
}
