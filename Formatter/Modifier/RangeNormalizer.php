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
use Rollerworks\RecordFilterBundle\Exception\ValidationException;
use Rollerworks\RecordFilterBundle\Type\FilterTypeInterface;
use Rollerworks\RecordFilterBundle\FilterConfig;
use Rollerworks\RecordFilterBundle\Value\FilterValuesBag;
use Rollerworks\RecordFilterBundle\Value\Range;
use Rollerworks\RecordFilterBundle\Value\SingleValue;

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
     * {@inheritdoc}
     */
    protected $messages = array();

    /**
     * @var array
     */
    protected $removeIndexes = array();

    /**
     * @var FilterTypeInterface
     */
    protected $type;

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
     * Add an new message to the list
     *
     * @param string $transMessage
     * @param array  $params
     */
    protected function addMessage($transMessage, $params = array())
    {
        $this->messages[] = array('message' => $transMessage, 'params' => $params);
    }

    /**
     * {@inheritdoc}
     */
    public function modFilters(FormatterInterface $formatter, FilterConfig $filterConfig, FilterValuesBag $filterStruct, $groupIndex)
    {
        $this->messages = array();
        $this->removeIndexes = array();

        if (!$filterConfig->hasType() || (!$filterStruct->hasRanges() && !$filterStruct->hasExcludedRanges())) {
            return true;
        }

        $this->valuesBag = $filterStruct;
        $this->type = $filterConfig->getType();
        $type = $filterConfig->getType();

        $values = $filterStruct->getSingleValues();
        $ranges = $filterStruct->getRanges();

        // Ranges as index => value, for checking existence later on
        $rangesValues = array();

        foreach ($ranges as $valIndex => $range) {
            // Value is overlapping in range
            foreach ($values as $myIndex => $singeValue) {
                if ($this->isValInRange($singeValue, $range)) {
                    $this->addMessage('value_in_range', array(
                        '%value%' => '"' . $values[$myIndex]->getOriginalValue() . '"',
                        '%range%' => self::getRangeQuoted($ranges[$valIndex])));

                    $this->unsetVal($myIndex);
                    unset($values[$myIndex]);
                }
            }

            // Range is connected to other range
            foreach ($ranges as $myIndex => $myRange) {
                if ($myIndex === $valIndex) {
                    continue;
                }

                if ($type->isEquals($range->getUpper(), $myRange->getLower())) {
                    $this->addMessage('range_connected', array(
                        '%range1%' => self::getRangeQuoted($ranges[$valIndex]),
                        '%range2%' => self::getRangeQuoted($ranges[$myIndex]),
                        '%range3%' => self::getRangeQuoted($ranges[$valIndex], $ranges[$myIndex]),
                    ));

                    $range->setUpper($myRange->getUpper());

                    $this->unsetRange($myIndex);
                    unset($ranges[$myIndex]);
                }
                // Range overlaps in other range
                elseif ($type->isLower($myRange->getUpper(), $range->getUpper()) && $type->isHigher($myRange->getLower(), $range->getLower())) {
                    $this->addMessage('range_overlap', array(
                        '%range1%' => self::getRangeQuoted($ranges[$myIndex]),
                        '%range2%' => self::getRangeQuoted($ranges[$valIndex]),
                    ));

                    $this->unsetRange($myIndex);
                    unset($ranges[$myIndex]);
                }
            }

            if (isset($ranges[$valIndex])) {
                $rangesValues[$valIndex] = $ranges[$valIndex]->getLower() . '-' . $ranges[$valIndex]->getUpper();
            }
        }

        if ($filterStruct->hasExcludedRanges()) {
            $rangesExcludes = $filterStruct->getExcludedRanges();
            $excludes = $filterStruct->getExcludes();

            foreach ($rangesExcludes as $valIndex => $range) {
                // Value is overlapping in range
                foreach ($excludes as $myIndex => $singeValue) {
                    if ($this->isValInRange($singeValue, $range)) {
                        $this->addMessage('value_in_range', array(
                            '%value%' => '!"' . $excludes[$myIndex]->getOriginalValue() . '"',
                            '%range%' => '!' . self::getRangeQuoted($rangesExcludes[$valIndex])));

                        $this->unsetVal($myIndex, true);
                        unset($excludes[$myIndex]);
                    }
                }

                // Range is connected to other range
                foreach ($rangesExcludes as $myIndex => $myRange) {
                    if ($myIndex === $valIndex) {
                        continue;
                    }

                    if ($type->isEquals($range->getUpper(), $myRange->getLower())) {
                        $this->addMessage('range_connected', array(
                            '%range1%' => '!' . self::getRangeQuoted($rangesExcludes[$valIndex]),
                            '%range2%' => '!' . self::getRangeQuoted($rangesExcludes[$myIndex]),
                            '%range3%' => '!' . self::getRangeQuoted($rangesExcludes[$valIndex], $rangesExcludes[$myIndex]),
                        ));

                        $range->setUpper($myRange->getUpper());

                        $this->unsetRange($myIndex, true);
                        unset($rangesExcludes[$myIndex]);
                    }

                    // Range overlaps in other range
                    if ($type->isLower($myRange->getUpper(), $range->getUpper()) && $type->isHigher($myRange->getLower(), $range->getLower())) {
                        $this->addMessage('range_overlap', array(
                            '%range1%' => '!' . self::getRangeQuoted($rangesExcludes[$myIndex]),
                            '%range2%' => '!' . self::getRangeQuoted($rangesExcludes[$valIndex]),
                        ));

                        $this->unsetRange($myIndex, true);
                        unset($rangesExcludes[$myIndex]);
                    }
                }

                // Range already exists as normal range
                if (false !== array_search($range->getLower() . '-' . $range->getUpper(), $rangesValues)) {
                    throw new ValidationException('range_same_as_excluded', '!' . self::getRangeQuoted($ranges[$valIndex]));
                }
            }
        }

        return $this->removeIndexes;
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
            $this->valuesBag->removeExclude($index);
        }
        else {
            $this->valuesBag->removeSingleValue($index);
        }

        $this->removeIndexes[] = $index;
    }

    /**
     * Remove an range-value
     *
     * @param integer $index
     * @param boolean $exclude
     */
    protected function unsetRange($index, $exclude = false)
    {
        if ($exclude) {
            $this->valuesBag->removeExcludedRange($index);
        }
        else {
            $this->valuesBag->removeRange($index);
        }

        $this->removeIndexes[] = $index;
    }

    /**
     * Returns the 'original' range values between quotes.
     *
     * @param Range $range
     * @param Range $range2
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
     * Checks if the value is overlapping in the range
     *
     * @param SingleValue $singeValue
     * @param Range       $range
     * @return boolean
     */
    protected function isValInRange(SingleValue $singeValue, Range $range)
    {
        if ($this->type->isLower($singeValue->getValue(), $range->getUpper()) && $this->type->isHigher($singeValue->getValue(), $range->getLower()))
        {
            return true;
        }
        elseif ($this->type->isEquals($singeValue->getValue(), $range->getUpper()) && $this->type->isEquals($singeValue->getValue(), $range->getLower())) {
            return true;
        }
        else {
            return false;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getMessages()
    {
        return $this->messages;
    }
}
