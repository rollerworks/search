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

use Rollerworks\RecordFilterBundle\MessageBag;
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
    public function validateValue($input, &$message = null, MessageBag $messageBag = null)
    {
        $message = 'This is not an valid customer.';

        return ctype_digit($input);
    }

    /**
     * {@inheritdoc}
     */
    public function formatOutput($value)
    {
        return $value->getCustomerId();
    }

    /**
     * {@inheritdoc}
     */
    public function dumpValue($input)
    {
        return $input->getCustomerId();
    }
}
