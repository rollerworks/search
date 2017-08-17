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

namespace Rollerworks\Component\Search\Test;

use PHPUnit\Framework\TestCase;
use Rollerworks\Component\Search\Exception\SearchException;
use Rollerworks\Component\Search\Extension\Core\Type\IntegerType;
use Rollerworks\Component\Search\Extension\Core\Type\TextType;
use Rollerworks\Component\Search\GenericFieldSetBuilder;
use Rollerworks\Component\Search\Input\ProcessorConfig;
use Rollerworks\Component\Search\InputProcessor;
use Rollerworks\Component\Search\SearchCondition;
use Rollerworks\Component\Search\Searches;
use Rollerworks\Component\Search\SearchFactory;
use Rollerworks\Component\Search\SearchFactoryBuilder;
use Rollerworks\Component\Search\Tests\Input\InputProcessorTestCase;
use Rollerworks\Component\Search\Value\ValuesBag;
use Rollerworks\Component\Search\Value\ValuesGroup;

/**
 * @author Sebastiaan Stok <s.stok@rollerscapes.net>
 */
abstract class SearchIntegrationTestCase extends TestCase
{
    /**
     * @var SearchFactoryBuilder|null
     */
    protected $factoryBuilder;

    /**
     * @var SearchFactory|null
     */
    private $searchFactory;

    protected function setUp()
    {
        parent::setUp();

        $this->factoryBuilder = Searches::createSearchFactoryBuilder();
    }

    protected function getFactory(): SearchFactory
    {
        if (null === $this->searchFactory) {
            $this->factoryBuilder->addExtensions($this->getExtensions());
            $this->factoryBuilder->addTypes($this->getTypes());
            $this->factoryBuilder->addTypeExtensions($this->getTypeExtensions());

            $this->searchFactory = $this->factoryBuilder->getSearchFactory();
        }

        return $this->searchFactory;
    }

    protected function getExtensions(): array
    {
        return [];
    }

    protected function getTypes(): array
    {
        return [];
    }

    protected function getTypeExtensions(): array
    {
        return [];
    }

    /**
     * @param bool $build
     *
     * @return \Rollerworks\Component\Search\FieldSet|GenericFieldSetBuilder
     */
    protected function getFieldSet(bool $build = true)
    {
        $fieldSet = new GenericFieldSetBuilder($this->getFactory());
        $fieldSet->set($this->getFactory()->createField('id', IntegerType::class));
        $fieldSet->add('name', TextType::class);

        return $build ? $fieldSet->getFieldSet() : $fieldSet;
    }

    protected static function assertConditionsEquals(SearchCondition $expectedCondition, SearchCondition $actualCondition)
    {
        try {
            // First try the "simple" method, it's possible this fails due to index mismatches.
            self::assertEquals($expectedCondition, $actualCondition);
        } catch (\Exception $e) {
            // No need for custom implementations here.
            // The reindexValuesGroup can be used for custom implementations (when needed).
            $actualCondition = new SearchCondition(
                $actualCondition->getFieldSet(),
                self::reindexValuesGroup($actualCondition->getValuesGroup())
            );

            self::assertEquals($expectedCondition, $actualCondition);
        }
    }

    protected static function reindexValuesGroup(ValuesGroup $valuesGroup): ValuesGroup
    {
        $newValuesGroup = new ValuesGroup($valuesGroup->getGroupLogical());

        foreach ($valuesGroup->getGroups() as $group) {
            $newValuesGroup->addGroup(self::reindexValuesGroup($group));
        }

        foreach ($valuesGroup->getFields() as $name => $valuesBag) {
            $newValuesBag = new ValuesBag();

            foreach ($valuesBag->getSimpleValues() as $value) {
                $newValuesBag->addSimpleValue($value);
            }

            foreach ($valuesBag->getExcludedSimpleValues() as $value) {
                $newValuesBag->addExcludedSimpleValue($value);
            }

            // use array_merge to renumber indexes and prevent mismatches.
            foreach ($valuesBag->all() as $type => $values) {
                foreach (array_merge([], $values) as $value) {
                    $newValuesBag->add($value);
                }
            }

            $newValuesGroup->addField($name, $newValuesBag);
        }

        return $newValuesGroup;
    }

    protected function assertConditionEquals(
        $input,
        SearchCondition $condition,
        InputProcessor $processor,
        ProcessorConfig $config
    ) {
        try {
            self::assertEquals($condition, $processor->process($config, $input));
        } catch (\Exception $e) {
            InputProcessorTestCase::detectSystemException($e);

            if (function_exists('dump')) {
                dump($e);
            } else {
                echo 'Please install symfony/var-dumper as dev-requirement to get a readable structure.'.PHP_EOL;

                // Don't use var-dump or print-r as this crashes php...
                echo get_class($e).'::'.(string) $e;
            }

            $this->fail('Condition contains errors.');
        }
    }

    protected static function detectSystemException(\Exception $exception)
    {
        if (!$exception instanceof SearchException) {
            throw $exception;
        }
    }
}
