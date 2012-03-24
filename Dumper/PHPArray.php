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
 * Dump the filtering preferences as 'flat' PHP Array.
 *
 * The values are flattened, meaning that the values are in one array and not per type.
 * To get the array with types its better to serialize the result of getFilters().
 *
 * @author Sebastiaan Stok <s.stok@rollerscapes.net>
 */
class PHPArray extends AbstractDumper
{
    /**
     * Returns the filtering preference as an PHP Array.
     *
     * Each entry value is a group with the fields and there values (as Array)
     *
     * @param \Rollerworks\RecordFilterBundle\Formatter\FormatterInterface $formatter
     * @return array
     */
    public function dumpFilters(FormatterInterface $formatter)
    {
        $aFilters = array();

        foreach ($formatter->getFilters() as $groupIndex => $fields) {
            foreach ($fields as $field => $values) {
                $aFilters[$groupIndex][$field] = self::filterStructToArray($values);
            }
        }

        return $aFilters;
    }
}
