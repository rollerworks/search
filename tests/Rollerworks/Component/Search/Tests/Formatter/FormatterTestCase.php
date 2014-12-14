<?php

/**
 * This file is part of the RollerworksSearch Component package.
 *
 * (c) 2012-2014 Sebastiaan Stok <s.stok@rollerscapes.net>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Rollerworks\Component\Search\Tests\Formatter;

use Rollerworks\Component\Search\FieldSet;
use Rollerworks\Component\Search\FieldSetBuilder;
use Rollerworks\Component\Search\FormatterInterface;
use Rollerworks\Component\Search\Searches;
use Rollerworks\Component\Search\SearchFactory;
use Rollerworks\Component\Search\ValuesBag;

abstract class FormatterTestCase extends \PHPUnit_Framework_TestCase
{
    /**
     * @var FieldSet
     */
    protected $fieldSet;

    /**
     * @var SearchFactory
     */
    protected $searchFactory;

    /**
     * @var FormatterInterface
     */
    protected $formatter;

    protected function setUp()
    {
        parent::setUp();

        $this->searchFactory = Searches::createSearchFactoryBuilder()->getSearchFactory();

        $fieldSet = new FieldSetBuilder('test', $this->searchFactory);
        $fieldSet->add($this->searchFactory->createField('id', 'integer')->setAcceptRange(true));
        $fieldSet->add('name', 'text');

        $this->fieldSet = $fieldSet->getFieldSet();
    }

    protected function assertValueBagsEqual(ValuesBag $expected, ValuesBag $result)
    {
        $expectedArray = array(
            'single' => $expected->getSingleValues(),
            'excluded' => $expected->getExcludedValues(),
            'ranges' => $expected->getRanges(),
            'excludedRanges' => $expected->getExcludedRanges(),
            'compares' => $expected->getComparisons(),
            'matchers' => $expected->getPatternMatchers(),
        );

        // use array_merge to renumber indexes and prevent mismatches
        $resultArray = array(
            'single' => array_merge(array(), $result->getSingleValues()),
            'excluded' => array_merge(array(), $result->getExcludedValues()),
            'ranges' => array_merge(array(), $result->getRanges()),
            'excludedRanges' => array_merge(array(), $result->getExcludedRanges()),
            'compares' => array_merge(array(), $result->getComparisons()),
            'matchers' => array_merge(array(), $result->getPatternMatchers()),
        );

        $this->assertEquals($expectedArray, $resultArray);
    }
}
