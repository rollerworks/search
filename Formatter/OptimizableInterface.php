<?php

/**
 * This file is part of the RollerworksRecordFilterBundle.
 *
 * (c) Sebastiaan Stok <s.stok@rollerscapes.net>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
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