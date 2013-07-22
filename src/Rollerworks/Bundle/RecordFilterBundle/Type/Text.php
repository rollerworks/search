<?php

/**
 * This file is part of the RollerworksRecordFilterBundle.
 *
 * (c) Sebastiaan Stok <s.stok@rollerscapes.net>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Rollerworks\Bundle\RecordFilterBundle\Type;

use Rollerworks\Bundle\RecordFilterBundle\MessageBag;

/**
 * Text filter-type.
 *
 * This type can be extended for more precise type handling.
 *
 * @author Sebastiaan Stok <s.stok@rollerscapes.net>
 */
class Text implements FilterTypeInterface
{
    /**
     * {@inheritdoc}
     */
    public function sanitizeString($value)
    {
        return (string) $value;
    }

    /**
     * {@inheritdoc}
     */
    public function formatOutput($value)
    {
        return $value;
    }

    /**
     * {@inheritdoc}
     */
    public function dumpValue($value)
    {
        return $value;
    }

    /**
     * {@inheritdoc}
     */
    public function isHigher($value, $nextValue)
    {
        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function isLower($value, $nextValue)
    {
        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function isEqual($value, $nextValue)
    {
        return ($value === $nextValue);
    }

    /**
     * {@inheritdoc}
     */
    public function validateValue($value, MessageBag $messageBag)
    {
    }
}
