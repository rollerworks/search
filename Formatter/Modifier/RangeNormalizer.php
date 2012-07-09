<?php

/**
 * This file is part of the RollerworksRecordFilterBundle.
 *
 * (c) Sebastiaan Stok <s.stok@rollerscapes.net>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Rollerworks\Bundle\RecordFilterBundle\Formatter\Modifier;

use Rollerworks\Bundle\RecordFilterBundle\Formatter\FormatterInterface;
use Rollerworks\Bundle\RecordFilterBundle\MessageBag;
use Rollerworks\Bundle\RecordFilterBundle\Type\FilterTypeInterface;
use Rollerworks\Bundle\RecordFilterBundle\FilterField;
use Rollerworks\Bundle\RecordFilterBundle\Value\FilterValuesBag;
use Rollerworks\Bundle\RecordFilterBundle\Value\Range;
use Rollerworks\Bundle\RecordFilterBundle\Value\SingleValue;

/**
 * Removes overlapping ranges/values and merges connected ranges.
 *
 * This should be run after validation.
 *
 * @author Sebastiaan Stok <s.stok@rollerscapes.net>
 */
class RangeNormalizer implements ModifierInterface
{
    /**
     * @var FilterValuesBag
     */
    protected $valuesBag;

    /**
     * {@inheritdoc}
     */
    public function getModifierName()
    {
        return 'rangeNormalizer';
    }

    /**
     * {@inheritdoc}
     */
    public function modFilters(FormatterInterface $formatter, MessageBag $messageBag, FilterField $filterConfig, FilterValuesBag $filterStruct, $groupIndex)
    {
        if (!$filterConfig->hasType() || (!$filterStruct->hasRanges() && !$filterStruct->hasExcludedRanges())) {
            return true;
        }

        $this->valuesBag = $filterStruct;
        $type = $filterConfig->getType();

        $isError = false;

        $values = $filterStruct->getSingleValues();
        $ranges = $filterStruct->getRanges();

        // Ranges as index => value, for checking existence later on
        $rangesValues = array();

        foreach ($ranges as $valIndex => $range) {
            // Value is overlapping in range
            foreach ($values as $myIndex => $singeValue) {
                if ($this->isValInRange($type, $singeValue, $range)) {
                    $messageBag->addInfo('value_in_range', array(
                        '{{ value }}' => '"' . $values[$myIndex]->getOriginalValue() . '"' ,
                        '{{ range }}' => self::getRangeQuoted($ranges[$valIndex])));

                    $this->unsetVal($myIndex);
                    unset($values[$myIndex]);
                }
            }

            // Range is connected to other range
            foreach ($ranges as $myIndex => $myRange) {
                if ($myIndex === $valIndex) {
                    continue;
                }

                if ($type->isEqual($range->getUpper(), $myRange->getLower())) {
                    $messageBag->addInfo('range_connected', array(
                        '{{ range1 }}' => self::getRangeQuoted($ranges[$valIndex]),
                        '{{ range2 }}' => self::getRangeQuoted($ranges[$myIndex]),
                        '{{ range3 }}' => self::getRangeQuoted($ranges[$valIndex], $ranges[$myIndex]),
                    ));

                    $range->setUpper($myRange->getUpper());

                    $this->unsetRange($myIndex);
                    unset($ranges[$myIndex]);
                }
                // Range overlaps in other range
                elseif ($type->isLower($myRange->getUpper(), $range->getUpper()) && $type->isHigher($myRange->getLower(), $range->getLower())) {
                    $messageBag->addInfo('range_overlap', array(
                        '{{ range1 }}' => self::getRangeQuoted($ranges[$myIndex]),
                        '{{ range2 }}' => self::getRangeQuoted($ranges[$valIndex]),
                    ));

                    $this->unsetRange($myIndex);
                    unset($ranges[$myIndex]);
                }
            }

            if (isset($ranges[$valIndex])) {
                $rangesValues[$valIndex] = $type->dumpValue($ranges[$valIndex]->getLower()) . '-' . $type->dumpValue($ranges[$valIndex]->getUpper());
            }
        }

        if ($filterStruct->hasExcludedRanges()) {
            $rangesExcludes = $filterStruct->getExcludedRanges();
            $excludes = $filterStruct->getExcludes();

            foreach ($rangesExcludes as $valIndex => $range) {
                // Value is overlapping in range
                foreach ($excludes as $myIndex => $singeValue) {
                    if ($this->isValInRange($type, $singeValue, $range)) {
                        $messageBag->addInfo('value_in_range', array(
                            '{{ value }}' => '!"' . $singeValue->getOriginalValue() . '"',
                            '{{ range }}' => '!' . self::getRangeQuoted($range)));

                        $this->unsetVal($myIndex, true);
                        unset($excludes[$myIndex]);
                    }
                }

                // Range is connected to other range
                foreach ($rangesExcludes as $myIndex => $myRange) {
                    if ($myIndex === $valIndex) {
                        continue;
                    }

                    if ($type->isEqual($range->getUpper(), $myRange->getLower())) {
                        $messageBag->addInfo('range_connected', array(
                            '{{ range1 }}' => '!' . self::getRangeQuoted($range),
                            '{{ range2 }}' => '!' . self::getRangeQuoted($myRange),
                            '{{ range3 }}' => '!' . self::getRangeQuoted($range, $myRange),
                        ));

                        $range->setUpper($myRange->getUpper());

                        $this->unsetRange($myIndex, true);
                        unset($rangesExcludes[$myIndex]);
                    }

                    // Range overlaps in other range
                    if ($type->isLower($myRange->getUpper(), $range->getUpper()) && $type->isHigher($myRange->getLower(), $range->getLower())) {
                        $messageBag->addInfo('range_overlap', array(
                            '{{ range1 }}' => '!' . self::getRangeQuoted($myRange),
                            '{{ range2 }}' => '!' . self::getRangeQuoted($range),
                        ));

                        $this->unsetRange($myIndex, true);
                        unset($rangesExcludes[$myIndex]);
                    }
                }

                // Range already exists as normal range
                if (false !== array_search($type->dumpValue($range->getLower()) . '-' . $type->dumpValue($range->getUpper()), $rangesValues)) {
                    $messageBag->addError('range_same_as_excluded', array('{{ value }}' => self::getRangeQuoted($range)));

                    $isError = true;
                }
            }
        }

        return !$isError;
    }

    /**
     * Removes an single-value.
     *
     * @param integer $index
     * @param boolean $exclude
     */
    protected function unsetVal($index, $exclude = false)
    {
        if ($exclude) {
            $this->valuesBag->removeExclude($index);
        } else {
            $this->valuesBag->removeSingleValue($index);
        }
    }

    /**
     * Removes an range-value.
     *
     * @param integer $index
     * @param boolean $exclude
     */
    protected function unsetRange($index, $exclude = false)
    {
        if ($exclude) {
            $this->valuesBag->removeExcludedRange($index);
        } else {
            $this->valuesBag->removeRange($index);
        }
    }

    /**
     * Returns the 'original' range values between quotes.
     *
     * @param Range $range
     * @param Range $range2
     *
     * @return string
     */
    protected static function getRangeQuoted(Range $range, Range $range2 = null)
    {
        if (null === $range2) {
            $range2 = $range;
        }

        return '"' . $range->getOriginalLower() . '"-"' . $range2->getOriginalUpper() . '"';
    }

    /**
     * Returns whether $singeValue is overlapping in $range.
     *
     * @param FilterTypeInterface $type
     * @param SingleValue         $singeValue
     * @param Range               $range
     *
     * @return boolean
     */
    protected function isValInRange($type, SingleValue $singeValue, Range $range)
    {
        if (
            ($type->isLower($singeValue->getValue(), $range->getUpper()) && $type->isHigher($singeValue->getValue(), $range->getLower()))
        ||
            ($type->isEqual($singeValue->getValue(), $range->getUpper()) && $type->isEqual($singeValue->getValue(), $range->getLower()))
        ) {
            return true;
        }

        return false;
    }
}
