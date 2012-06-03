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

use Rollerworks\RecordFilterBundle\Formatter\MessageBag;
use Rollerworks\RecordFilterBundle\Value\FilterValuesBag;

/**
 * Filter-value optimizable interface.
 *
 * Implement this interface in the filter-type if the values can be optimized.
 * Optimizing includes removing redundant values and changing the filtering strategy.
 *
 * An example can be, where you have an 'Status' type which only accepts 'active', 'not-active' and 'remove'.
 * If ***all*** the possible values are chosen, the values are redundant and the filter should be removed.
 *
 * @author Sebastiaan Stok <s.stok@rollerscapes.net>
 */
interface OptimizableInterface
{
    /**
     * Optimize the Field.
     *
     * The first value is the FilterValuesBag with all the field values.
     *
     * To remove one value-object, cal remove[Range|ExcludedRange|SingeValue|Exclude|Compare]() with the value index.
     * To remove an range at value-index 1 call removeRange(1). If the index is none existent it's ignored.
     *
     * Return false to remove the field completely.
     *
     * Message handling is done using the MessageBag class.
     * Adding an message will automatically translate it and add the required placeholders (%label% and %group%).
     *
     * @param FilterValuesBag $field
     * @param MessageBag      $messageBag
     *
     * @param boolean|void
     *
     * @api
     */
    public function optimizeField(FilterValuesBag $field, MessageBag $messageBag);
}
