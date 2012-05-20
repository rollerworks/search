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

use Rollerworks\RecordFilterBundle\Type\FilterTypeInterface;

class CustomerType implements FilterTypeInterface
{
    /**
     * {@inheritdoc}
     */
    public function sanitizeString($input)
    {
        return new CustomerUser($input);
    }

    /**
     * {@inheritdoc}
     */
    public function isHigher($input, $nextValue)
    {
        return $input->getCustomerId() > $nextValue->getCustomerId();
    }

    /**
     * {@inheritdoc}
     */
    public function isLower($input, $nextValue)
    {
        return $input->getCustomerId() < $nextValue->getCustomerId();
    }

    /**
     * {@inheritdoc}
     */
    public function isEquals($input, $nextValue)
    {
        return ($input->getCustomerId() === $nextValue->getCustomerId());
    }

    /**
     * {@inheritdoc}
     */
    public function validateValue($input, &$message = null)
    {
        $message = 'This is not an valid customer.';

        return ctype_digit($input);
    }

    /**
     * Formats the value for display and return it as a string.
     *
     * This function does the opposite of sanitizeString().
     *
     * @param mixed $value
     * @return string
     */
    public function formatOutput($value)
    {
        return $value->getCustomerId();
    }

    /**
     * Returns the scalar representation of the value.
     *
     * This is used for duplicate detection and debugging.
     *
     * @param mixed $input
     * @return string
     */
    public function dumpValue($input)
    {
        return $input->getCustomerId();
    }
}
