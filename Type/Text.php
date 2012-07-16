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
 * Text filter type.
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
    public function sanitizeString($input)
    {
        return (string) $input;
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
    public function dumpValue($input)
    {
        return $input;
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
        return true;
    }
}
