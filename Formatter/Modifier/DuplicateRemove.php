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

use Rollerworks\RecordFilterBundle\FilterStruct;
use Rollerworks\RecordFilterBundle\Formatter\FilterConfig;
use Rollerworks\RecordFilterBundle\Formatter\FormatterInterface;

/**
 * Validate and formats the filters.
 * After this the values can be considered valid.
 *
 * @author Sebastiaan Stok <s.stok@rollerscapes.net>
 */
class DuplicateRemove implements PostModifierInterface
{
    /**
     * Optimizer messages
     *
     * @var array
     */
    protected $messages = array();

    /**
     * Index list of removed values
     *
     * @var array
     */
    protected $removedIndexes = array();

    /**
     * {@inheritdoc}
     */
    public function getModifierName()
    {
        return 'duplicateRemove';
    }

    /**
     * @param integer $index
     * @param string  $value
     */
    protected function informDuplicate($index, $value)
    {
        $this->messages[]       = array('duplicate', array('%value%' => $value));
        $this->removedIndexes[] = $index;
    }

    /**
     * {@inheritdoc}
     */
    public function modFilters(FormatterInterface $formatter, FilterConfig $filterConfig, FilterStruct $filterStruct, $groupIndex)
    {
        $ranges         = array();
        $excludedRanges = array();

        $excludedValues = array();
        $compares       = array();
        $singleValues   = array();

        $this->messages       = array();
        $this->removedIndexes = array();

        foreach ($filterStruct->getSingleValues() as $index => $value) {
            $_value = $value->getValue();

            if (in_array($_value, $singleValues)) {
                $this->informDuplicate($index, '"' . $value->getOriginalValue() . '"');
                $filterStruct->removeSingleValue($index);

                continue;
            }

            $singleValues[] = $_value;
        }

        foreach ($filterStruct->getExcludes() as $index => $value) {
            $_value = $value->getValue();

            if (in_array($_value, $excludedValues)) {
                $this->informDuplicate($index, '!"' . $value->getOriginalValue() . '"');
                $filterStruct->removeExclude($index);

                continue;
            }

            $excludedValues[] = $_value;
        }

        foreach ($filterStruct->getRanges() as $index => $range) {
            $_value = $range->getLower() . '-' . $range->getHigher();

            if (in_array($_value, $ranges)) {
                $this->informDuplicate($index, '"' . $range->getOriginalLower() . '"-"' . $range->getOriginalHigher() . '"');
                $filterStruct->removeRange($index);

                continue;
            }

            $ranges[] = $_value;
        }

        foreach ($filterStruct->getExcludedRanges() as $index => $range) {
            $_value = $range->getLower() . '-' . $range->getHigher();

            if (in_array($_value, $excludedRanges)) {
                $this->informDuplicate($index, '!"' . $range->getOriginalLower() . '"-"' . $range->getOriginalHigher() . '"');
                $filterStruct->removeExcludedRange($index);

                continue;
            }

            $excludedRanges[] = $_value;
        }

        foreach ($filterStruct->getCompares() as $index => $compare) {
            $_value = $compare->getOperator() . $compare->getValue();

            if (in_array($_value, $compares)) {
                $this->informDuplicate($index, $compare->getOperator() . '"' . $compare->getOriginalValue() . '"');
                $filterStruct->removeCompare($index);

                continue;
            }

            $compares[] = $_value;
        }

        return $this->removedIndexes;
    }

    /**
     * {@inheritdoc}
     */
    public function getMessages()
    {
        return $this->messages;
    }
}
