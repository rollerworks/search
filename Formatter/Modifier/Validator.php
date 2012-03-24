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
use Rollerworks\RecordFilterBundle\Formatter\FilterType;
use Rollerworks\RecordFilterBundle\Formatter\FormatterInterface;
use Rollerworks\RecordFilterBundle\Struct\Range;

use Rollerworks\RecordFilterBundle\Formatter\Exception\ValidationException;

/**
 * Validates the values and formats the value with the sanitized version
 * After this the values can be considered valid.
 *
 * @author Sebastiaan Stok <s.stok@rollerscapes.net>
 */
class Validator implements PostModifierInterface
{
    /**
     * {@inheritdoc}
     */
    public function getModifierName()
    {
        return 'validator';
    }

    /**
     * {@inheritdoc}
     */
    public function modFilters(FormatterInterface $formatter, FilterConfig $filterConfig, FilterStruct $filterStruct, $groupIndex)
    {
        if (!$filterConfig->hasType()) {
            return true;
        }

        $ranges         = array();
        $excludedRanges = array();

        $excludedValues = array();
        $singleValues   = array();

        $type = $filterConfig->getType();

        foreach ($filterStruct->getSingleValues() as $value) {
            $this->validateValue($type, $value->getValue());

            $_value = $type->sanitizeString($value->getValue());

            if (in_array($_value, $excludedValues)) {
                throw new ValidationException('value_in_exclude', $value->getOriginalValue());
            }

            $singleValues[] = $_value;
            $value->setValue($_value);
        }

        foreach ($filterStruct->getExcludes() as $value) {
            $this->validateValue($type, $value->getValue(), '!' . $value->getValue());

            $_value = $type->sanitizeString($value->getValue());

            if (in_array($_value, $singleValues)) {
                throw new ValidationException('value_in_include', '!' . $value->getOriginalValue());
            }

            $excludedValues[] = $_value;
            $value->setValue($_value);
        }

        foreach ($filterStruct->getRanges() as $range) {
            $this->validateValue($type, $range->getLower(), $range->getLower() . '-' . $range->getHigher());
            $this->validateValue($type, $range->getHigher(), $range->getLower() . '-' . $range->getHigher());

            $range->setLower($type->sanitizeString($range->getLower()));
            $range->setHigher($type->sanitizeString($range->getHigher()));

            $this->validateRange($type, $range);

            $_value = $range->getLower() . '-' . $range->getHigher();

            if (in_array($_value, $excludedRanges)) {
                throw new ValidationException('value_in_exclude', $range->getOriginalLower() . '-' . $range->getOriginalHigher());
            }

            $ranges[] = $_value;
        }

        foreach ($filterStruct->getExcludedRanges() as $range) {
            $this->validateValue($type, $range->getLower(), '!' . $range->getLower() . '-' . $range->getHigher());
            $this->validateValue($type, $range->getHigher(), '!' . $range->getLower() . '-' . $range->getHigher());

            $range->setLower($type->sanitizeString($range->getLower()));
            $range->setHigher($type->sanitizeString($range->getHigher()));

            $this->validateRange($type, $range);

            $_value = $range->getLower() . '-' . $range->getHigher();

            if (in_array($_value, $ranges)) {
                throw new ValidationException('range_same_as_excluded', '!"' . $range->getOriginalLower() . '"-"' . $range->getOriginalHigher() . '"');
            }

            $excludedRanges[] = $_value;
        }

        foreach ($filterStruct->getCompares() as $compare) {
            $this->validateValue($type, $compare->getValue(), $compare->getOperator() . $compare->getValue());

            $compare->setValue($type->sanitizeString($compare->getValue()));
        }

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function getMessages()
    {
        return array();
    }

    /**
     * Validates an 'single' value and throws an ValidationException in case of failure.
     *
     * @param \Rollerworks\RecordFilterBundle\Formatter\FilterType $type
     * @param string                                                         $value
     * @param string                                                         $originalValue
     * @throws \Rollerworks\RecordFilterBundle\Formatter\Exception\ValidationException In case of an validation error
     */
    protected function validateValue(FilterType $type, $value, $originalValue = null)
    {
        $sMessage = '';

        if (!strlen($originalValue)) {
            $originalValue = $value;
        }

        if (!$type->validateValue($value, $sMessage)) {
            throw new ValidationException('validation_warning', $originalValue, array('%msg%' => $sMessage));
        }
    }

    /**
     * Validates an range value and throws an ValidationException in case of failure.
     *
     * @param \Rollerworks\RecordFilterBundle\Formatter\FilterType $type
     * @param \Rollerworks\RecordFilterBundle\Struct\Range         $range
     * @throws \Rollerworks\RecordFilterBundle\Formatter\Exception\ValidationException
     */
    protected function validateRange(FilterType $type, Range $range)
    {
        if (!$type->isLower($range->getLower(), $range->getHigher())) {
            throw new ValidationException('not_lower', $range->getOriginalLower().'-'.$range->getOriginalHigher(), array(
                '%value1%' => $range->getOriginalLower(),
                '%value2%' => $range->getOriginalHigher()));
        }
    }
}
