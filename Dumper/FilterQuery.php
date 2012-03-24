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

use Rollerworks\RecordFilterBundle\Formatter\FormatterInterface;

/**
 * Dump the filtering preferences as RecordFilter FilterQuery string.
 *
 * @see \Rollerworks\RecordFilterBundle\Input
 *
 * @author Sebastiaan Stok <s.stok@rollerscapes.net>
 */
class FilterQuery extends AbstractDumper
{
    /**
     * Returns the filtering preference as an 'raw' FilterQuery string.
     *
     * Single values and ranges are always quoted.
     *
     * @param \Rollerworks\RecordFilterBundle\Formatter\FormatterInterface $formatter
     * @param bool                                                                   $fieldPerLine  Return each field on a new line
     * @return string
     */
    public function dumpFilters(FormatterInterface $formatter, $fieldPerLine = false)
    {
        $filterQuery = '';

        foreach ($formatter->getFilters() as $fields) {
            $filterQuery .= '( ';

            foreach ($fields as $label => $values) {
                $filterQuery .= $label . '=' . implode(', ', self::filterStructToArray($values, true)) . '; ';

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
}
