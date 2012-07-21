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
 * FilterTypeInterface.
 *
 * An field filter-type must implement this interface.
 * The input for comparing values is always sanitized.
 *
 * @author Sebastiaan Stok <s.stok@rollerscapes.net>
 *
 * @api
 */
interface FilterTypeInterface
{
    /**
     * Sanitize the input string to an normal useful value.
     *
     * @param mixed $value
     *
     * @return mixed
     *
     * @api
     */
    public function sanitizeString($value);

    /**
     * Formats the value for display and return it as a string.
     *
     * This function does the opposite of sanitizeString().
     *
     * @param mixed $value
     *
     * @return string
     *
     * @api
     */
    public function formatOutput($value);

    /**
     * Returns the scalar representation of the value.
     *
     * This is used for duplicate detection and debugging.
     *
     * @param mixed $value
     *
     * @return string
     *
     * @api
     */
    public function dumpValue($value);

    /**
     * Returns whether the first value is higher then the second.
     *
     * @param mixed $value
     * @param mixed $nextValue
     *
     * @return boolean
     *
     * @api
     */
    public function isHigher($value, $nextValue);

    /**
     * Returns whether the first value is lower then the second.
     *
     * @param mixed $value
     * @param mixed $nextValue
     *
     * @return boolean
     *
     * @api
     */
    public function isLower($value, $nextValue);

    /**
     * Returns whether the first value equals then the second.
     *
     * @param mixed $value
     * @param mixed $nextValue
     *
     * @return boolean
     *
     * @api
     */
    public function isEqual($value, $nextValue);

    /**
     * Returns whether the input value is legally formatted.
     *
     * Optionally the MessageBag can be used for adding multiple messages.
     * Set $message to null in that case.
     *
     * @param mixed        $value
     * @param string|array $message
     * @param MessageBag   $messageBag
     *
     * @return boolean
     *
     * @api
     */
    public function validateValue($value, &$message = null, MessageBag $messageBag = null);
}
