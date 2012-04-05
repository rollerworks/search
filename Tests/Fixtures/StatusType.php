<?php

/**
 * This file is part of the RollerworksRecordFilterBundle.
 *
 * (c) Sebastiaan Stok <s.stok@rollerscapes.net>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Rollerworks\RecordFilterBundle\Tests\Fixtures;

use Rollerworks\RecordFilterBundle\FilterTypeInterface;
use Rollerworks\RecordFilterBundle\Formatter\OptimizableInterface;
use Rollerworks\RecordFilterBundle\FilterStruct;

class StatusType implements FilterTypeInterface, OptimizableInterface
{
    /**
     * {@inheritdoc}
     */
    public function sanitizeString($input)
    {
        $replacement      = array('Active', 'Not-active', 'Removed');
        $replacementValue = array(1, 0, -1);

        return str_replace($replacement, $replacementValue, mb_strtolower($input));
    }

    /**
     * {@inheritdoc}
     */
    public function isHigher($input, $nextValue)
    {
        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function isLower($input, $nextValue)
    {
        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function isEquals($input, $nextValue)
    {
        return ($input === $nextValue);
    }

    /**
     * {@inheritdoc}
     */
    public function validateValue($input, &$message = null)
    {
        $message = 'This is not an valid status';

        $input = $this->sanitizeString($input);

        return in_array($input, array(1, 0, -1));
    }

    /**
     * {@inheritdoc}
     */
    public function optimizeField(FilterStruct $field, &$paMessage)
    {
        // Since there are no duplicates and only three values are legal.
        return (count($field->getSingleValues()) === 3 ? null : true);
    }
}