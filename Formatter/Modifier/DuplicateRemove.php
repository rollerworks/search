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
     * Optimizer messages
     *
     * @var array
     */
    protected $messages = array();

    /**
     * Index list of removed values
     *
     * @var integer[]
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
     * {@inheritdoc}
     */
    public function modFilters(FormatterInterface $formatter, FilterConfig $filterConfig, FilterValuesBag $filterStruct, $groupIndex)
    {
        $ranges = $excludedRanges = $excludedValues = $compares = $singleValues = array();
        $this->messages = $this->removedIndexes = array();

        $type = $filterConfig->getType();

        foreach ($filterStruct->getSingleValues() as $index => $value) {
            $_value = ($type ? $type->dumpValue($value->getValue()) : $value->getValue());

            if (in_array($_value, $singleValues)) {
                $this->informDuplicate($index, '"' . $value->getOriginalValue() . '"');
                $filterStruct->removeSingleValue($index);

                continue;
            }

            $singleValues[] = $_value;
        }

        foreach ($filterStruct->getExcludes() as $index => $value) {
            $_value = ($type ? $type->dumpValue($value->getValue()) : $value->getValue());

            if (in_array($_value, $excludedValues)) {
                $this->informDuplicate($index, '!"' . $value->getOriginalValue() . '"');
                $filterStruct->removeExclude($index);

                continue;
            }

            $excludedValues[] = $_value;
        }

        foreach ($filterStruct->getRanges() as $index => $range) {
            $_value = $this->dumpRange($type, $range);

            if (in_array($_value, $ranges)) {
                $this->informDuplicate($index, '"' . $range->getOriginalLower() . '"-"' . $range->getOriginalUpper() . '"');
                $filterStruct->removeRange($index);

                continue;
            }

            $ranges[] = $_value;
        }

        foreach ($filterStruct->getExcludedRanges() as $index => $range) {
            $_value = $this->dumpRange($type, $range);

            if (in_array($_value, $excludedRanges)) {
                $this->informDuplicate($index, '!"' . $range->getOriginalLower() . '"-"' . $range->getOriginalUpper() . '"');
                $filterStruct->removeExcludedRange($index);

                continue;
            }

            $excludedRanges[] = $_value;
        }

        foreach ($filterStruct->getCompares() as $index => $compare) {
            $_value = $compare->getOperator() . ($type ? $type->dumpValue($compare->getValue()) : $compare->getValue());

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

    /**
     * @param integer $index
     * @param string  $value
     */
    protected function informDuplicate($index, $value)
    {
        $this->messages[]       = array('message' => 'duplicate', 'params' => array('%value%' => $value));
        $this->removedIndexes[] = $index;
    }

    /**
     * @param FilterTypeInterface $type
     * @param Range               $range
     * @return string
     * @throws \RuntimeException
     */
    protected function dumpRange(FilterTypeInterface $type = null, Range $range)
    {
        if ($type) {
            return $type->dumpValue($range->getLower()) . '-' . $type->dumpValue($range->getUpper());
        }

        return $range->getLower() . '-' . $range->getUpper();
    }
}
