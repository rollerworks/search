<?php

/**
 * This file is part of the RollerworksRecordFilterBundle.
 *
 * (c) Rollerscapes
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU Lesser General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Lesser General Public License for more details.
 *
 * You should have received a copy of the GNU Lesser General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 * @link    http://projects.rollerscapes.net/RollerFramework
 * @license http://www.opensource.org/licenses/lgpl-license.php LGPL
 */

namespace Rollerworks\RecordFilterBundle\Formatter\Modifier;

use Rollerworks\RecordFilterBundle\Formatter\Exception\ValidationException;
use Rollerworks\RecordFilterBundle\Formatter\FormatterInterface;
use Rollerworks\RecordFilterBundle\Formatter\FilterConfig;
use Rollerworks\RecordFilterBundle\Formatter\FilterType;
use Rollerworks\RecordFilterBundle\Struct\Range;
use Rollerworks\RecordFilterBundle\Struct\Value;
use Rollerworks\RecordFilterBundle\FilterStruct;

/**
 * Validate and formats the filters.
 * After this the values can be considered valid.
 *
 * @author Sebastiaan Stok <s.stok@rollerscapes.net>
 */
class RangeNormalizer implements PostModifierInterface
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
     * @var FilterType
     */
    protected $type;

    /**
     * @var FilterStruct
     */
    protected $filterStruct;

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
     * @param string  $transMessage
     * @param array   $params
     */
    protected function addMessage($transMessage, $params = array())
    {
        $this->messages[] = array($transMessage, $params);
    }

    /**
     * {@inheritdoc}
     */
    public function modFilters(FormatterInterface $formatter, FilterConfig $filterConfig, FilterStruct $filterStruct, $groupIndex)
    {
        $this->messages = array();

        if (!$filterConfig->hasType() || (!$filterStruct->hasRanges() && !$filterStruct->hasExcludedRanges())) {
            return true;
        }

        $this->removeIndexes = array();
        $this->filterStruct  = $filterStruct;
        $this->type          = $filterConfig->getType();

        $type = $filterConfig->getType();

        $values = $filterStruct->getSingleValues();
        $ranges = $filterStruct->getRanges();

        // Ranges as index => value, for checking existence later on
        $rangesValues = array();

        // TODO Convert connected values-lists to an ranges (needs test-cases and interface first)

        /**
         * @var \Rollerworks\RecordFilterBundle\Struct\Range $range
         * @var \Rollerworks\RecordFilterBundle\Struct\Range $myRange
         * @var \Rollerworks\RecordFilterBundle\Struct\Value $singeValue
         */

        foreach ($ranges as $valIndex => $range) {
            // Value is overlapping in range
            foreach ($values as $myIndex => $singeValue) {
                if ($this->isValInRange($singeValue, $range)) {
                    $this->addMessage('value_in_range', array(
                        '%value%' => '"' . $values[ $myIndex ]->getOriginalValue() . '"',
                        '%range%' => self::getRangeQuoted($ranges[ $valIndex ])));

                    $this->unsetVal($myIndex);
                    unset($values[ $myIndex ]);
                }
            }

            // Range is connected to other range
            foreach ($ranges as $myIndex => $myRange) {
                if ($myIndex === $valIndex) {
                    continue;
                }

                if ($type->isEquals($range->getUpper(), $myRange->getLower())) {
                    $this->addMessage('range_connected', array(
                        '%range1%' => self::getRangeQuoted($ranges[ $valIndex ]),
                        '%range2%' => self::getRangeQuoted($ranges[ $myIndex ]),
                        '%range3%' => self::getRangeQuoted($ranges[ $valIndex ], $ranges[ $myIndex ]),
                    ));

                    $range->setUpper($myRange->getUpper());

                    $this->unsetRange($myIndex);
                    unset($ranges[ $myIndex ]);
                }
                // Range overlaps in other range
                elseif ($type->isLower($myRange->getUpper(), $range->getUpper()) && $type->isHigher($myRange->getLower(), $range->getLower())) {
                    $this->addMessage('range_overlap', array(
                        '%range1%' => self::getRangeQuoted($ranges[ $myIndex ]),
                        '%range2%' => self::getRangeQuoted($ranges[ $valIndex ]),
                    ));

                    $this->unsetRange($myIndex);
                    unset($ranges[ $myIndex ]);
                }
            }

            if (isset($ranges[$valIndex])) {
                $rangesValues[$valIndex] = $ranges[ $valIndex ]->getLower() . '-' . $ranges[ $valIndex ]->getUpper();
            }
        }

        if ($filterStruct->hasExcludedRanges()) {
            $aRangesExcludes = $filterStruct->getExcludedRanges();
            $aExcludes       = $filterStruct->getExcludes();

            foreach ($aRangesExcludes as $valIndex => $range) {
                // Value is overlapping in range
                foreach ($aExcludes as $myIndex => $singeValue) {
                    if ($this->isValInRange($singeValue, $range)) {
                        $this->addMessage('value_in_range', array(
                            '%value%' => '!"' . $aExcludes[$myIndex]->getOriginalValue() . '"',
                            '%range%' => '!' . self::getRangeQuoted($aRangesExcludes[ $valIndex ])));

                        $this->unsetVal($myIndex, true);
                        unset($aExcludes[ $myIndex ]);
                    }
                }

                // Range is connected to other range
                foreach ($aRangesExcludes as $myIndex => $myRange) {
                    if ($myIndex === $valIndex) {
                        continue;
                    }

                    if ($type->isEquals($range->getUpper(), $myRange->getLower())) {
                        $this->addMessage('range_connected', array(
                            '%range1%' => '!' . self::getRangeQuoted($aRangesExcludes[ $valIndex ]),
                            '%range2%' => '!' . self::getRangeQuoted($aRangesExcludes[ $myIndex ]),
                            '%range3%' => '!' . self::getRangeQuoted($aRangesExcludes[ $valIndex ], $aRangesExcludes[ $myIndex ]),
                        ));

                        $range->setUpper($myRange->getUpper());

                        $this->unsetRange($myIndex, true);
                        unset($aRangesExcludes[ $myIndex ]);
                    }

                    // Range overlaps in other range
                    if ($type->isLower($myRange->getUpper(), $range->getUpper()) && $type->isHigher($myRange->getLower(), $range->getLower())) {
                        $this->addMessage('range_overlap', array(
                            '%range1%' => '!' . self::getRangeQuoted($aRangesExcludes[ $myIndex ]),
                            '%range2%' => '!' . self::getRangeQuoted($aRangesExcludes[ $valIndex ]),
                        ));

                        $this->unsetRange($myIndex, true);
                        unset($aRangesExcludes[ $myIndex ]);
                    }
                }

                // Range already exists as normal range
                if (false !== array_search($range->getLower() . '-' . $range->getUpper(), $rangesValues)) {
                    throw new ValidationException('range_same_as_excluded', '!' . self::getRangeQuoted($ranges[ $valIndex ]));
                }
            }
        }

        return $this->removeIndexes;
    }

    /**
     * Remove an single-value
     *
     * @param integer  $index
     * @param bool     $exclude
     */
    protected function unsetVal($index, $exclude = false)
    {
        if ($exclude) {
            $this->filterStruct->removeExclude($index);
        }
        else {
            $this->filterStruct->removeSingleValue($index);
        }

        $this->removeIndexes[] = $index;
    }

    /**
     * Remove an range-value
     *
     * @param integer   $index
     * @param bool      $exclude
     */
    protected function unsetRange($index, $exclude = false)
    {
        if ($exclude) {
            $this->filterStruct->removeExcludedRange($index);
        }
        else {
            $this->filterStruct->removeRange($index);
        }

        $this->removeIndexes[] = $index;
    }

    /**
     * Returns the 'original' range values between quotes.
     *
     * @param \Rollerworks\RecordFilterBundle\Struct\Range $range
     * @param \Rollerworks\RecordFilterBundle\Struct\Range $range2
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
     * @param \Rollerworks\RecordFilterBundle\Struct\Value $singeValue
     * @param \Rollerworks\RecordFilterBundle\Struct\Range $range
     * @return bool
     */
    protected function isValInRange(Value $singeValue, Range $range)
    {
        if (($this->type->isLower($singeValue->getValue(), $range->getUpper()) && $this->type->isHigher($singeValue->getValue(), $range->getLower())))
        {
            return true;
        } elseif ($this->type->isEquals($singeValue->getValue(), $range->getUpper()) && $this->type->isEquals($singeValue->getValue(), $range->getLower())) {
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
