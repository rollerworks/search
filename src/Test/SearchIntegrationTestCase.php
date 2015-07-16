<?php

/*
 * This file is part of the RollerworksSearch package.
 *
 * (c) Sebastiaan Stok <s.stok@rollerscapes.net>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Rollerworks\Component\Search\Test;

use Prophecy\Prophet;
use Rollerworks\Component\Search\FieldSetBuilder;
use Rollerworks\Component\Search\Searches;
use Rollerworks\Component\Search\SearchFactory;
use Rollerworks\Component\Search\SearchFactoryBuilder;
use Rollerworks\Component\Search\ValuesBag;

abstract class SearchIntegrationTestCase extends \PHPUnit_Framework_TestCase
{
    /**
     * @var SearchFactoryBuilder
     */
    protected $factoryBuilder;

    /**
     * @var SearchFactory
     */
    private $searchFactory;

    /**
     * @var Prophet
     */
    protected $prophet;

    protected function setUp()
    {
        parent::setUp();

        $this->prophet = new Prophet();
        $this->factoryBuilder = Searches::createSearchFactoryBuilder();
    }

    /**
     * @return SearchFactory
     */
    protected function getFactory()
    {
        if (null === $this->searchFactory) {
            $this->factoryBuilder->addExtensions($this->getExtensions());
            $this->factoryBuilder->addTypes($this->getTypes());
            $this->factoryBuilder->addTypeExtensions($this->getTypeExtensions());

            $this->searchFactory = $this->factoryBuilder->getSearchFactory();
        }

        return $this->searchFactory;
    }

    protected function tearDown()
    {
        if ($this->prophet) {
            $this->prophet->checkPredictions();
        }

        parent::tearDown();
    }

    protected function getExtensions()
    {
        return [];
    }

    protected function getTypes()
    {
        return [];
    }

    protected function getTypeExtensions()
    {
        return [];
    }

    /**
     * @param bool $build
     *
     * @return \Rollerworks\Component\Search\FieldSet|FieldSetBuilder
     */
    protected function getFieldSet($build = true)
    {
        $fieldSet = new FieldSetBuilder('test', $this->getFactory());
        $fieldSet->add($this->getFactory()->createField('id', 'integer'));
        $fieldSet->add('name', 'text');

        return $build ? $fieldSet->getFieldSet() : $fieldSet;
    }

    protected function assertValueBagsEqual(ValuesBag $expected, ValuesBag $result)
    {
        $expectedArray = [
            'single' => $expected->getSingleValues(),
            'excluded' => $expected->getExcludedValues(),
            'ranges' => $expected->getRanges(),
            'excludedRanges' => $expected->getExcludedRanges(),
            'compares' => $expected->getComparisons(),
            'matchers' => $expected->getPatternMatchers(),
        ];

        // use array_merge to renumber indexes and prevent mismatches
        $resultArray = [
            'single' => array_merge([], $result->getSingleValues()),
            'excluded' => array_merge([], $result->getExcludedValues()),
            'ranges' => array_merge([], $result->getRanges()),
            'excludedRanges' => array_merge([], $result->getExcludedRanges()),
            'compares' => array_merge([], $result->getComparisons()),
            'matchers' => array_merge([], $result->getPatternMatchers()),
        ];

        $this->assertEquals($expectedArray, $resultArray);
    }
}
