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

use Rollerworks\RecordFilterBundle\Struct\Value;

/**
 * Filter type supporting connected-list of values to ranges should impelement this interface.
 *
 * @author Sebastiaan Stok <s.stok@rollerscapes.net>
 */
interface ValuesToRangeInterface
{
    /**
     * Sorts the values input list.
     *
     * Must return an integer value depending on the comparison
     *
     * @link http://php.net/uasort
     *
     * @param Value $first
     * @param Value $second
     * @return integer
     */
    public function sortValuesList(Value $first, Value $second);

    /**
     * Returns the higher-by-one value of the input.
     *
     * Input value is normalized, and should be returned normalized.
     * The value should be increased by 'one', where one is considered the 'next' value of the input.
     *
     * Examples:
     * * 1+1 = 2
     * * 2011-12-15       + 1 = 2011-12-16
     * * 2011-12-31       + 1 = 2012-01-01
     * * 2011-12-31 12:00 + 1 = 2012-01-01 12:01
     * * 2012-00425       + 1 = 2012-00426 (Invoice)
     *
     * @param string|float|integer $input
     * @return string|float|integer
     */
    public function getHigherValue($input);
}