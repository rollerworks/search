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
use Rollerworks\RecordFilterBundle\FilterConfig;
use Rollerworks\RecordFilterBundle\FilterValuesBag;

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
    public function modFilters(FormatterInterface $formatter, FilterConfig $filterConfig, FilterValuesBag $filterStruct, $groupIndex)
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
            $_value = $range->getLower() . '-' . $range->getUpper();

            if (in_array($_value, $ranges)) {
                $this->informDuplicate($index, '"' . $range->getOriginalLower() . '"-"' . $range->getOriginalUpper() . '"');
                $filterStruct->removeRange($index);

                continue;
            }

            $ranges[] = $_value;
        }

        foreach ($filterStruct->getExcludedRanges() as $index => $range) {
            $_value = $range->getLower() . '-' . $range->getUpper();

            if (in_array($_value, $excludedRanges)) {
                $this->informDuplicate($index, '!"' . $range->getOriginalLower() . '"-"' . $range->getOriginalUpper() . '"');
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
