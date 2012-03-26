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

namespace Rollerworks\RecordFilterBundle\Dumper;

use Rollerworks\RecordFilterBundle\FilterStruct;
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
     * @param \Rollerworks\RecordFilterBundle\FilterStruct $filter
     * @param bool $quoteLooseValue
     * @return array
     */
    protected static function filterStructToArray(FilterStruct $filter, $quoteLooseValue = false)
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
