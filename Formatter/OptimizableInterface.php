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

namespace Rollerworks\RecordFilterBundle\Formatter;

use Rollerworks\RecordFilterBundle\FilterStruct;

/**
 * Filter-value optimizable interface.
 *
 * Implement this interface if the filter values can be optimized.
 * Optimizing includes removing redundant values and changing the filtering strategy.
 *
 * An example can be, where you have an 'Status' type which only accepts 'active', 'not-active' and 'remove'.
 * If ***all*** the possible values are chosen, the values are redundant and should be removed.
 *
 * @author Sebastiaan Stok <s.stok@rollerscapes.net>
 */
interface OptimizableInterface
{
    /**
     * Optimize the Field.
     *
     * The first value is the FilterStruct with all the field values.
     * @see \Rollerworks\RecordFilterBundle\FilterStruct
     *
     * To remove one value-object, cal remove[Range|ExcludedRange|SingeValue|Exclude|Compare]() with the value index.
     * To remove an range at value-index 1 call removeRange(1). If the index is none existent it's ignored.
     *
     * When actually removing values this function ***should*** return an array with the removed value-indexes.
     * This is for removing the values from the optimized values list.
     *
     * Return null to remove the field completly.
     *
     * $messages may contain an array of information messages.
     * ***These will be run trough the translator later on.***
     *
     * @param FilterStruct   $field
     * @param array          $messages
     * @param array|null
     */
    public function optimizeField(FilterStruct $field, &$messages);
}