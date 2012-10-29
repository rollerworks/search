<?php

/**
 * This file is part of the RollerworksRecordFilterBundle.
 *
 * (c) Sebastiaan Stok <s.stok@rollerscapes.net>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Rollerworks\Bundle\RecordFilterBundle\Formatter;

use Rollerworks\Bundle\RecordFilterBundle\Value\SingleValue;

/**
 * Filter type supporting connected-list of values to ranges should implement this interface.
 *
 * @author Sebastiaan Stok <s.stok@rollerscapes.net>
 *
 * @api
 */
interface ValuesToRangeInterface
{
    /**
     * Sorts the values input list.
     *
     * Must return an integer value depending on the comparison.
     *
     * @link http://php.net/usort
     *
     * @param SingleValue $first
     * @param SingleValue $second
     *
     * @return integer
     *
     * @api
     */
    public function sortValuesList($first, $second);

    /**
     * Returns the higher-by-one value of the input.
     *
     * Input value is sanitized, and the return value should be returned sanitized as well.
     * The value should be increased by 'one', where one is considered the 'next' value of the input.
     *
     * Examples:
     * * 1+1 = 2
     * * 2011-12-15       + 1 = 2011-12-16
     * * 2011-12-31       + 1 = 2012-01-01
     * * 2011-12-31 12:00 + 1 = 2012-01-01 12:01
     * * 2012-00425       + 1 = 2012-00426 (Invoice)
     *
     * @param mixed $input
     *
     * @return mixed
     */
    public function getHigherValue($input);
}
