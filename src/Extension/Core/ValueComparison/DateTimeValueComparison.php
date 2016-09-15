<?php

/*
 * This file is part of the RollerworksSearch package.
 *
 * (c) Sebastiaan Stok <s.stok@rollerscapes.net>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Rollerworks\Component\Search\Extension\Core\ValueComparison;

/**
 * @author Sebastiaan Stok <s.stok@rollerscapes.net>
 */
class DateTimeValueComparison extends DateValueComparison
{
    /**
     * Returns the incremented value of the input.
     *
     * The value should returned in the normalized format.
     *
     * @param \DateTime $value      The value to increment
     * @param array     $options    Array of options passed with the field
     * @param int       $increments Number of increments
     *
     * @return \DateTime
     */
    public function getIncrementedValue($value, array $options, $increments = 1)
    {
        $newValue = clone $value;

        if ($options['with_seconds']) {
            $newValue->modify('+'.$increments.' seconds');
        } elseif ($options['with_minutes']) {
            $newValue->modify('+'.$increments.' minutes');
        } else {
            $newValue->modify('+'.$increments.' hours');
        }

        return $newValue;
    }
}
