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

use Rollerworks\RecordFilterBundle\Formatter\Exception\ValidationException;
use Rollerworks\RecordFilterBundle\Formatter\FormatterInterface;
use Rollerworks\RecordFilterBundle\Formatter\FilterConfig;
use Rollerworks\RecordFilterBundle\Formatter\FilterTypeInterface;
use Rollerworks\RecordFilterBundle\Value\Range;
use Rollerworks\RecordFilterBundle\FilterStruct;

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
            $this->validateValue($type, $range->getLower(), $range->getLower() . '-' . $range->getUpper());
            $this->validateValue($type, $range->getUpper(), $range->getLower() . '-' . $range->getUpper());

            $range->setLower($type->sanitizeString($range->getLower()));
            $range->setUpper($type->sanitizeString($range->getUpper()));

            $this->validateRange($type, $range);

            $_value = $range->getLower() . '-' . $range->getUpper();

            if (in_array($_value, $excludedRanges)) {
                throw new ValidationException('value_in_exclude', $range->getOriginalLower() . '-' . $range->getOriginalUpper());
            }

            $ranges[] = $_value;
        }

        foreach ($filterStruct->getExcludedRanges() as $range) {
            $this->validateValue($type, $range->getLower(), '!' . $range->getLower() . '-' . $range->getUpper());
            $this->validateValue($type, $range->getUpper(), '!' . $range->getLower() . '-' . $range->getUpper());

            $range->setLower($type->sanitizeString($range->getLower()));
            $range->setUpper($type->sanitizeString($range->getUpper()));

            $this->validateRange($type, $range);

            $_value = $range->getLower() . '-' . $range->getUpper();

            if (in_array($_value, $ranges)) {
                throw new ValidationException('range_same_as_excluded', '!"' . $range->getOriginalLower() . '"-"' . $range->getOriginalUpper() . '"');
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
     * @param \Rollerworks\RecordFilterBundle\Formatter\FilterTypeInterface $type
     * @param string                                                         $value
     * @param string                                                         $originalValue
     * @throws \Rollerworks\RecordFilterBundle\Formatter\Exception\ValidationException In case of an validation error
     */
    protected function validateValue(FilterTypeInterface $type, $value, $originalValue = null)
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
     * @param \Rollerworks\RecordFilterBundle\Formatter\FilterTypeInterface $type
     * @param \Rollerworks\RecordFilterBundle\Struct\Range         $range
     * @throws \Rollerworks\RecordFilterBundle\Formatter\Exception\ValidationException
     */
    protected function validateRange(FilterTypeInterface $type, Range $range)
    {
        if (!$type->isLower($range->getLower(), $range->getUpper())) {
            throw new ValidationException('not_lower', $range->getOriginalLower().'-'.$range->getOriginalUpper(), array(
                '%value1%' => $range->getOriginalLower(),
                '%value2%' => $range->getOriginalUpper()));
        }
    }
}
