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
use Rollerworks\Component\Search\Exception\ExceptionInterface;
use Rollerworks\Component\Search\Extension\Core\Type\IntegerType;
use Rollerworks\Component\Search\Extension\Core\Type\TextType;
use Rollerworks\Component\Search\FieldSetBuilder;
use Rollerworks\Component\Search\Input\ProcessorConfig;
use Rollerworks\Component\Search\InputProcessorInterface;
use Rollerworks\Component\Search\SearchCondition;
use Rollerworks\Component\Search\Searches;
use Rollerworks\Component\Search\SearchFactory;
use Rollerworks\Component\Search\SearchFactoryBuilder;
use Rollerworks\Component\Search\Tests\Input\InputProcessorTestCase;
use Rollerworks\Component\Search\Value\Compare;
use Rollerworks\Component\Search\Value\ExcludedRange;
use Rollerworks\Component\Search\Value\PatternMatch;
use Rollerworks\Component\Search\Value\Range;
use Rollerworks\Component\Search\Value\ValuesBag;

abstract class SearchIntegrationTestCase extends TestCase
{
    /**
     * @var SearchFactoryBuilder
     */
    protected $factoryBuilder;

    /**
     * @var SearchFactory
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
     * @return \Rollerworks\Component\Search\FieldSet|FieldSetBuilder
     */
    protected function getFieldSet(bool $build = true)
    {
        $fieldSet = new FieldSetBuilder($this->getFactory());
        $fieldSet->set($this->getFactory()->createField('id', IntegerType::class));
        $fieldSet->add('name', TextType::class);

        return $build ? $fieldSet->getFieldSet() : $fieldSet;
    }

    protected static function assertValueBagsEqual(ValuesBag $expected, ValuesBag $result)
    {
        $expectedArray = [
            'single' => $expected->getSimpleValues(),
            'excluded' => $expected->getExcludedSimpleValues(),
            'ranges' => $expected->get(Range::class),
            'excludedRanges' => $expected->get(ExcludedRange::class),
            'compares' => $expected->get(Compare::class),
            'matchers' => $expected->get(PatternMatch::class),
        ];

        // use array_merge to renumber indexes and prevent mismatches
        $resultArray = [
            'single' => array_merge([], $result->getSimpleValues()),
            'excluded' => array_merge([], $result->getExcludedSimpleValues()),
            'ranges' => array_merge([], $result->get(Range::class)),
            'excludedRanges' => array_merge([], $result->get(ExcludedRange::class)),
            'compares' => array_merge([], $result->get(Compare::class)),
            'matchers' => array_merge([], $result->get(PatternMatch::class)),
        ];

        self::assertEquals($expectedArray, $resultArray);
    }

    protected function assertConditionEquals(
        $input,
        SearchCondition $condition,
        InputProcessorInterface $processor,
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
        if (!$exception instanceof ExceptionInterface) {
            throw $exception;
        }
    }
}
