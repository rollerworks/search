<?php

/**
 * This file is part of the RollerworksRecordFilterBundle.
 *
 * (c) Sebastiaan Stok <s.stok@rollerscapes.net>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Rollerworks\Bundle\RecordFilterBundle\Tests\Fixtures;

use Rollerworks\Bundle\RecordFilterBundle\Type\FilterTypeInterface;
use Rollerworks\Bundle\RecordFilterBundle\Formatter\OptimizableInterface;
use Rollerworks\Bundle\RecordFilterBundle\MessageBag;
use Rollerworks\Bundle\RecordFilterBundle\Value\FilterValuesBag;

class StatusType implements FilterTypeInterface, OptimizableInterface
{
    /**
     * {@inheritdoc}
     */
    public function sanitizeString($input)
    {
        $replacement      = array('not-active', 'active', 'removed');
        $replacementValue = array(0, 1, -1);

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
    public function isEqual($input, $nextValue)
    {
        return ($input === $nextValue);
    }

    /**
     * {@inheritdoc}
     */
    public function validateValue($input, &$message = null, MessageBag $messageBag = null)
    {
        $message = 'This is not an valid status';

        $input = $this->sanitizeString($input);

        return in_array($input, array(1, 0, -1));
    }

    /**
     * {@inheritdoc}
     */
    public function optimizeField(FilterValuesBag $field, MessageBag $messageBag)
    {
        // Since there are no duplicates and only three values are legal.
        return (count($field->getSingleValues()) === 3 ? false : true);
    }

    /**
     * Formats the value for display and return it as a string.
     *
     * This function does the opposite of sanitizeString().
     *
     * @param mixed $value
     *
     * @return string
     */
    public function formatOutput($value)
    {
        return $value;
    }

    /**
     * Returns the scalar representation of the value.
     *
     * This is used for duplicate detection and debugging.
     *
     * @param mixed $input
     *
     * @return string
     */
    public function dumpValue($input)
    {
        return $input;
    }
}
