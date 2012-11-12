<?php

/**
 * This file is part of the RollerworksRecordFilterBundle.
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
 * Exports the filtering preferences as a FilterQuery string.
 *
 * @see \Rollerworks\Bundle\RecordFilterBundle\Input\FilterQuery
 *
 * @author Sebastiaan Stok <s.stok@rollerscapes.net>
 */
class FilterQueryExporter extends AbstractExporter
{
    /**
     * {@inheritdoc}
     *
     * @return string
     */
    public function dumpFilters(FormatterInterface $formatter, $fieldPerLine = false)
    {
        $filterQuery = '';

        foreach ($formatter->getFilters() as $fields) {
            $filterQuery .= '( ';

            foreach ($fields as $name => $valuesBag) {
                if (!($label = $formatter->getFieldSet()->get($name)->getLabel())) {
                    $label = $name;
                }

                $filterQuery .= $label . '=' . implode(', ', self::valuesBagToArray($formatter, $label, $valuesBag, true)) . '; ';

                if ($fieldPerLine) {
                    $filterQuery = rtrim($filterQuery);
                    $filterQuery .= PHP_EOL;
                }
            }

            $filterQuery = rtrim($filterQuery) . ' ), ';
        }

        $filterQuery = rtrim($filterQuery, ', ');

        return $filterQuery;
    }

    /**
     * Return the array version of an FilterValuesBag object.
     *
     * The order is: singe-values, excluded-value, ranges, excluded-ranges, compares.
     *
     * Ranges will always have both-side values quoted like "lower"-"higher".
     * Single values are only quoted depending on $quoteLooseValue
     *
     * @param FormatterInterface $formatter
     * @param string             $fieldName
     * @param FilterValuesBag    $filter
     * @param boolean            $quoteLooseValue
     *
     * @return array
     */
    protected static function valuesBagToArray(FormatterInterface $formatter, $fieldName, FilterValuesBag $filter, $quoteLooseValue = false)
    {
        $type = $formatter->getFieldSet()->get($fieldName)->getType();
        $filters = array();

        foreach ($filter->getSingleValues() as $value) {
            $value = self::formatValue($type, $value->getValue());

            if ($quoteLooseValue) {
                $value = self::quoteValue($value);
            }

            $filters[] = $value;
        }

        foreach ($filter->getExcludes() as $value) {
            $value = self::formatValue($type, $value->getValue());

            if ($quoteLooseValue) {
                $value = self::quoteValue($value);
            }

            $filters[] = '!' . $value;
        }

        foreach ($filter->getRanges() as $range) {
            $filters[] = self::quoteValue(self::formatValue($type, $range->getLower())) . '-' . self::quoteValue(self::formatValue($type, $range->getUpper()));
        }

        foreach ($filter->getExcludedRanges() as $range) {
            $filters[] = '!' . self::quoteValue(self::formatValue($type, $range->getLower())) . '-' . self::quoteValue(self::formatValue($type, $range->getUpper()));
        }

        foreach ($filter->getCompares() as $value) {
            $filters[] = $value->getOperator() . self::formatValue($type, $value->getValue());
        }

        return $filters;
    }

    /**
     * Quote an value and escape the quotes.
     *
     * @param string $input
     *
     * @return string
     */
    protected static function quoteValue($input)
    {
        return '"' . str_replace('"', '""', $input) . '"';
    }
}
