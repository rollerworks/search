<?php

/**
 * This file is part of the RollerworksRecordFilterBundle.
 *
 * (c) Sebastiaan Stok <s.stok@rollerscapes.net>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Rollerworks\RecordFilterBundle\Dumper;

use Rollerworks\RecordFilterBundle\Value\FilterValuesBag;
use Rollerworks\RecordFilterBundle\Formatter\FormatterInterface;

/**
 * AbstractDumper
 *
 * @author Sebastiaan Stok <s.stok@rollerscapes.net>
 */
abstract class AbstractDumper implements DumperInterface
{
    /**
     * Return the array version of an FilterStruct object.
     *
     * The order is: singe-values, single-value-excludes, ranges, excluded-ranges, compares.
     *
     * Ranges will always have both-side values quoted like "lower"-"higher".
     * Single values are only quoted depending on $quoteLooseValue
     *
     * @param FilterValuesBag $filter
     * @param boolean         $quoteLooseValue
     * @return array
     */
    protected static function filterStructToArray(FilterValuesBag $filter, $quoteLooseValue = false)
    {
        $filters = array();

        foreach ($filter->getSingleValues() as $value) {
            if ($quoteLooseValue) {
                $value = self::quoteValue((string) $value);
            }

            $filters[] = (string) $value;
        }

        foreach ($filter->getExcludes() as $value) {
            if ($quoteLooseValue) {
                $value = self::quoteValue((string) $value);
            }

            $filters[] = '!' . (string) $value;
        }

        foreach ($filter->getRanges() as $range) {
            $filters[] = self::quoteValue($range->getLower()) . '-' . self::quoteValue($range->getUpper());
        }

        foreach ($filter->getExcludedRanges() as $range) {
            $filters[] = '!' . self::quoteValue($range->getLower()) . '-' . self::quoteValue($range->getUpper());
        }

        foreach ($filter->getCompares() as $value) {
            $filters[] = (string) $value;
        }

        return $filters;
    }

    /**
     * Quote an value and escape the quotes
     *
     * @param string $input
     * @return string
     */
    protected static function quoteValue($input)
    {
        return '"' . str_replace('"', '""', $input) . '"';
    }
}
