<?php

/*
 * This file is part of the RollerworksRecordFilterBundle package.
 *
 * (c) Sebastiaan Stok <s.stok@rollerscapes.net>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Rollerworks\Bundle\RecordFilterBundle\Exporter;

use Rollerworks\Bundle\RecordFilterBundle\Formatter\FormatterInterface;
use Rollerworks\Bundle\RecordFilterBundle\Value\FilterValuesBag;

/**
 * Exports the filtering preference as a structured PHP Array.
 *
 * @see \Rollerworks\Bundle\RecordFilterBundle\Input\ArrayInput
 *
 * @author Sebastiaan Stok <s.stok@rollerscapes.net>
 */
class ArrayExporter extends AbstractExporter
{
    /**
     * {@inheritdoc}
     *
     * @return array
     */
    public function dumpFilters(FormatterInterface $formatter)
    {
        $filters = array();

        foreach ($formatter->getFilters() as $groupIndex => $fields) {
            foreach ($fields as $field => $values) {
                $filters[$groupIndex][$field] = self::valuesBagToArray($formatter, $field, $values);
            }
        }

        return $filters;
    }

    /**
     * @param FormatterInterface $formatter
     * @param string             $fieldName
     * @param FilterValuesBag    $filter
     *
     * @return array
     */
    protected static function valuesBagToArray(FormatterInterface $formatter, $fieldName, FilterValuesBag $filter)
    {
        $type = $formatter->getFieldSet()->get($fieldName)->getType();
        $field = array();

        if ($filter->hasSingleValues()) {
            $field['single-values'] = array();

            foreach ($filter->getSingleValues() as $value) {
                $field['single-values'][] = self::dumpValue($type, $value->getValue());
            }
        }

        if ($filter->hasExcludes()) {
            $field['excluded-values'] = array();

            foreach ($filter->getExcludes() as $value) {
                $field['excluded-values'][] = self::dumpValue($type, $value->getValue());
            }
        }

        if ($filter->hasRanges()) {
            $field['ranges'] = array();

            foreach ($filter->getRanges() as $range) {
                $field['ranges'] = array('lower' => self::dumpValue($type, $range->getLower()),
                                         'higher' => self::dumpValue($type, $range->getUpper()));
            }
        }

        if ($filter->hasExcludedRanges()) {
            $field['excluded-ranges'] = array();

            foreach ($filter->getExcludedRanges() as $range) {
                $field['excluded-ranges'] = array('lower' => self::dumpValue($type, $range->getLower()),
                                                  'higher' => self::dumpValue($type, $range->getUpper()));
            }
        }

        if ($filter->hasCompares()) {
            $field['compares'] = array();

            foreach ($filter->getCompares() as $compare) {
                $field['compares'][] = array('operator' => self::dumpValue($type, $compare->getOperator()),
                                             'value' => self::dumpValue($type, $compare->getValue()));
            }
        }

        return $field;
    }
}
