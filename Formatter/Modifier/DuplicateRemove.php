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
use Rollerworks\RecordFilterBundle\MessageBag;
use Rollerworks\RecordFilterBundle\Type\FilterTypeInterface;
use Rollerworks\RecordFilterBundle\FilterConfig;
use Rollerworks\RecordFilterBundle\Value\FilterValuesBag;
use Rollerworks\RecordFilterBundle\Value\Range;

/**
 * Removes duplicate values.
 *
 * @author Sebastiaan Stok <s.stok@rollerscapes.net>
 */
class DuplicateRemove implements ModifierInterface
{
    /**
     * {@inheritdoc}
     */
    public function getModifierName()
    {
        return 'duplicateRemove';
    }

    /**
     * {@inheritdoc}
     */
    public function modFilters(FormatterInterface $formatter, MessageBag $messageBag, FilterConfig $filterConfig, FilterValuesBag $filterStruct, $groupIndex)
    {
        $ranges = $excludedRanges = $excludedValues = $compares = $singleValues = array();
        $type = $filterConfig->getType();

        foreach ($filterStruct->getSingleValues() as $index => $value) {
            $_value = ($type ? $type->dumpValue($value->getValue()) : $value->getValue());

            if (in_array($_value, $singleValues)) {
                $messageBag->addInfo('duplicate', array('{{ value }}' => '"' . $value->getOriginalValue() . '"'));
                $filterStruct->removeSingleValue($index);

                continue;
            }

            $singleValues[] = $_value;
        }

        foreach ($filterStruct->getExcludes() as $index => $value) {
            $_value = ($type ? $type->dumpValue($value->getValue()) : $value->getValue());

            if (in_array($_value, $excludedValues)) {
                $messageBag->addInfo('duplicate', array('{{ value }}' => '!"' . $value->getOriginalValue() . '"'));
                $filterStruct->removeExclude($index);

                continue;
            }

            $excludedValues[] = $_value;
        }

        foreach ($filterStruct->getRanges() as $index => $range) {
            $_value = $this->dumpRange($type, $range);

            if (in_array($_value, $ranges)) {
                $messageBag->addInfo('duplicate', array('{{ value }}' => self::getRangeQuoted($range)));
                $filterStruct->removeRange($index);

                continue;
            }

            $ranges[] = $_value;
        }

        foreach ($filterStruct->getExcludedRanges() as $index => $range) {
            $_value = $this->dumpRange($type, $range);

            if (in_array($_value, $excludedRanges)) {
                $messageBag->addInfo('duplicate', array('{{ value }}' => '!' . self::getRangeQuoted($range)));
                $filterStruct->removeExcludedRange($index);

                continue;
            }

            $excludedRanges[] = $_value;
        }

        foreach ($filterStruct->getCompares() as $index => $compare) {
            $_value = $compare->getOperator() . ($type ? $type->dumpValue($compare->getValue()) : $compare->getValue());

            if (in_array($_value, $compares)) {
                $messageBag->addInfo('duplicate', array('{{ value }}' => $compare->getOperator() . '"' . $compare->getOriginalValue() . '"'));
                $filterStruct->removeCompare($index);

                continue;
            }

            $compares[] = $_value;
        }

        return true;
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
     * @param FilterTypeInterface $type
     * @param Range               $range
     *
     * @return string
     */
    protected function dumpRange(FilterTypeInterface $type = null, Range $range)
    {
        if (null !== $type) {
            return $type->dumpValue($range->getLower()) . '-' . $type->dumpValue($range->getUpper());
        }

        return $range->getLower() . '-' . $range->getUpper();
    }
}
