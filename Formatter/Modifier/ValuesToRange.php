<?php

/**
 * This file is part of the RollerworksRecordFilterBundle.
 *
 * (c) Sebastiaan Stok <s.stok@rollerscapes.net>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Rollerworks\RecordFilterBundle\Formatter\Modifier;

use Rollerworks\RecordFilterBundle\Formatter\FormatterInterface;
use Rollerworks\RecordFilterBundle\Formatter\MessageBag;
use Rollerworks\RecordFilterBundle\Formatter\ValuesToRangeInterface;
use Rollerworks\RecordFilterBundle\Type\FilterTypeInterface;
use Rollerworks\RecordFilterBundle\FilterConfig;
use Rollerworks\RecordFilterBundle\Value\FilterValuesBag;
use Rollerworks\RecordFilterBundle\Value\Range;
use Rollerworks\RecordFilterBundle\Value\SingleValue;

/**
 * Converts a connected-list of values to ranges.
 *
 * 1,2,3,4,5 is converted to 1-5.
 * 1,2,3,4,5,7,9,11,12,13 is converted to 1-5,7,9,11-13.
 *
 * For this to work properly the filter-type must implement the ValuesToRangeInterface
 *
 * @author Sebastiaan Stok <s.stok@rollerscapes.net>
 */
class ValuesToRange implements ModifierInterface
{
    /**
     * @var FilterValuesBag
     */
    protected $filterStruct;

    /**
     * {@inheritdoc}
     */
    public function getModifierName()
    {
        return 'listToRange';
    }

    /**
     * {@inheritdoc}
     */
    public function modFilters(FormatterInterface $formatter, MessageBag $messageBag, FilterConfig $filterConfig, FilterValuesBag $filterStruct, $groupIndex)
    {
        if (!$filterConfig->hasType() || !$filterConfig->getType() instanceof ValuesToRangeInterface || (!$filterStruct->hasSingleValues() && !$filterStruct->hasExcludes())) {
            return true;
        }

        $this->filterStruct = $filterStruct;

        /** @var ValuesToRangeInterface $type */
        $type = $filterConfig->getType();

        if ($filterStruct->hasSingleValues()) {
            $values = $filterStruct->getSingleValues();
            uasort($values, array(&$type, 'sortValuesList'));

            $this->listToRanges($values, $type);
        }

        if ($filterStruct->hasExcludes()) {
            $excludes = $filterStruct->getExcludes();
            uasort($excludes, array(&$type, 'sortValuesList'));

            $this->listToRanges($excludes, $type, true);
        }

        return true;
    }

    /**
     * Converts a list of values to ranges.
     *
     * @param SingleValue[]                              $values
     * @param ValuesToRangeInterface|FilterTypeInterface $type
     * @param boolean                                    $exclude
     */
    protected function listToRanges($values, ValuesToRangeInterface $type, $exclude = false)
    {
        $prevIndex = null;
        $prevValue = null;

        $rangeLower = null;
        $rangeUpper = null;

        $valuesCount = count($values);
        $curCount = 0;

        foreach ($values as $valIndex => $value) {
            $curCount++;

            if (null === $prevValue) {
                $prevIndex = $valIndex;
                $prevValue = $value;

                continue;
            }

            $increasedValue = $type->getHigherValue($prevValue->getValue());

            if ($type->isEquals($value->getValue(), $increasedValue)) {
                if (null === $rangeLower) {
                    $rangeLower = $prevValue;
                }

                $rangeUpper = $value;
            }

            if (null !== $rangeUpper) {
                $this->unsetVal($prevIndex, $exclude);

                if (!$type->isEquals($value->getValue(), $increasedValue) || $curCount == $valuesCount) {
                    $range = new Range($rangeLower->getValue(), $rangeUpper->getValue(), $rangeLower->getOriginalValue(), $rangeUpper->getOriginalValue());

                    if ($exclude) {
                        $this->filterStruct->addExcludedRange($range);
                    } else {
                        $this->filterStruct->addRange($range);
                    }

                    $this->unsetVal($prevIndex, $exclude);

                    if ($type->isEquals($value->getValue(), $increasedValue) && $curCount == $valuesCount) {
                        $this->unsetVal($valIndex, $exclude);
                    }

                    $rangeLower = $rangeUpper = null;
                }

                $prevIndex = $valIndex;
                $prevValue = $value;
            }
        }
    }

    /**
     * Remove an single-value
     *
     * @param integer $index
     * @param boolean $exclude
     */
    protected function unsetVal($index, $exclude = false)
    {
        if ($exclude) {
            $this->filterStruct->removeExclude($index);
        } else {
            $this->filterStruct->removeSingleValue($index);
        }
    }
}
