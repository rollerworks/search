<?php

/*
 * This file is part of the RollerworksRecordFilterBundle package.
 *
 * (c) Sebastiaan Stok <s.stok@rollerscapes.net>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Rollerworks\Bundle\RecordFilterBundle\Tests\Fixtures;

use Rollerworks\Bundle\RecordFilterBundle\MessageBag;
use Rollerworks\Bundle\RecordFilterBundle\Type\FilterTypeInterface;

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
    public function isEqual($input, $nextValue)
    {
        return ($input->getCustomerId() === $nextValue->getCustomerId());
    }

    /**
     * {@inheritdoc}
     */
    public function validateValue($input, MessageBag $messageBag)
    {
        if (!ctype_digit($input)) {
            $messageBag->addError('This is not an valid customer.');
        }
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
