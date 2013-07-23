<?php

/*
 * This file is part of the RollerworksRecordFilterBundle package.
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
    public function modFilters(FormatterInterface $formatter, MessageBag $messageBag, FilterField $filterConfig, FilterValuesBag $filterStruct, $groupIndex)
    {
        $ranges = $excludedRanges = $excludedValues = $compares = $singleValues = array();
        $type = $filterConfig->getType();

        foreach ($filterStruct->getSingleValues() as $index => $value) {
            $dumpValue = ($type ? $type->dumpValue($value->getValue()) : $value->getValue());

            if (in_array($dumpValue, $singleValues)) {
                $messageBag->addInfo('record_filter.duplicate', array('{{ value }}' => '"' . $value->getOriginalValue() . '"'));
                $filterStruct->removeSingleValue($index);

                continue;
            }

            $singleValues[] = $dumpValue;
        }

        foreach ($filterStruct->getExcludes() as $index => $value) {
            $dumpValue = ($type ? $type->dumpValue($value->getValue()) : $value->getValue());

            if (in_array($dumpValue, $excludedValues)) {
                $messageBag->addInfo('record_filter.duplicate', array('{{ value }}' => '!"' . $value->getOriginalValue() . '"'));
                $filterStruct->removeExclude($index);

                continue;
            }

            $excludedValues[] = $dumpValue;
        }

        foreach ($filterStruct->getRanges() as $index => $range) {
            $dumpValue = $this->dumpRange($type, $range);

            if (in_array($dumpValue, $ranges)) {
                $messageBag->addInfo('record_filter.duplicate', array('{{ value }}' => self::getRangeQuoted($range)));
                $filterStruct->removeRange($index);

                continue;
            }

            $ranges[] = $dumpValue;
        }

        foreach ($filterStruct->getExcludedRanges() as $index => $range) {
            $dumpValue = $this->dumpRange($type, $range);

            if (in_array($dumpValue, $excludedRanges)) {
                $messageBag->addInfo('record_filter.duplicate', array('{{ value }}' => '!' . self::getRangeQuoted($range)));
                $filterStruct->removeExcludedRange($index);

                continue;
            }

            $excludedRanges[] = $dumpValue;
        }

        foreach ($filterStruct->getCompares() as $index => $compare) {
            $dumpValue = $compare->getOperator() . ($type ? $type->dumpValue($compare->getValue()) : $compare->getValue());

            if (in_array($dumpValue, $compares)) {
                $messageBag->addInfo('record_filter.duplicate', array('{{ value }}' => $compare->getOperator() . '"' . $compare->getOriginalValue() . '"'));
                $filterStruct->removeCompare($index);

                continue;
            }

            $compares[] = $dumpValue;
        }

        return true;
    }

    /**
     * Returns the 'original' range values quoted.
     *
     * @param Range      $range
     * @param Range|null $range2
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
     * @param Range|null          $range
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
