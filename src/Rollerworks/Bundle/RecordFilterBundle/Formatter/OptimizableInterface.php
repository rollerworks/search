<?php

/*
 * This file is part of the RollerworksRecordFilterBundle package.
 *
 * (c) Sebastiaan Stok <s.stok@rollerscapes.net>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Rollerworks\Bundle\RecordFilterBundle\Formatter;

use Rollerworks\Bundle\RecordFilterBundle\MessageBag;
use Rollerworks\Bundle\RecordFilterBundle\Value\FilterValuesBag;

/**
 * Filter optimizable interface.
 *
 * Implement this interface in the filter-type if the values can be further optimized.
 * Optimizing includes: removing redundant values and changing the filtering strategy.
 *
 * An example can be, where you have a 'Status' type which only accepts 'active', 'not-active' and 'remove'.
 * If ***all*** the possible values are chosen, the values are redundant and can be removed.
 *
 * @author Sebastiaan Stok <s.stok@rollerscapes.net>
 *
 * @api
 */
interface OptimizableInterface
{
    /**
     * Optimizes the Field.
     *
     * The first value is the FilterValuesBag with all the field values.
     *
     * To remove one value-object, cal remove[Range|ExcludedRange|SingeValue|Exclude|Compare]() with the value index.
     * To remove a range at index 1 call removeRange(1). If the index is none existent it's ignored.
     *
     * Return false to remove the field completely.
     *
     * Message handling is done using a MessageBag class instance.
     * Adding a message to the MessageBag will automatically translate it and add the required placeholders ({{ label }} and {{ group }}).
     *
     * @param FilterValuesBag $field
     * @param MessageBag      $messageBag
     *
     * @return boolean|void
     *
     * @api
     */
    public function optimizeField(FilterValuesBag $field, MessageBag $messageBag);
}
