<?php

/**
 * This file is part of the RollerworksRecordFilterBundle.
 *
 * (c) Sebastiaan Stok <s.stok@rollerscapes.net>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Rollerworks\RecordFilterBundle\Type;

use Rollerworks\RecordFilterBundle\MessageBag;

/**
 * FilterTypeInterface.
 *
 * An field filter-type must implement this interface.
 * The input for comparing values is always sanitized.
 *
 * @author Sebastiaan Stok <s.stok@rollerscapes.net>
 */
interface FilterTypeInterface
{
    /**
     * Sanitize the input string to an normal useful value
     *
     * @param mixed $input
     *
     * @return mixed
     */
    public function sanitizeString($input);

    /**
     * Formats the value for display and return it as a string.
     *
     * This function does the opposite of sanitizeString().
     *
     * @param mixed $value
     *
     * @return string
     */
    public function formatOutput($value);

    /**
     * Returns the scalar representation of the value.
     *
     * This is used for duplicate detection and debugging.
     *
     * @param mixed $input
     *
     * @return string
     */
    public function dumpValue($input);

    /**
     * Returns whether the first value is higher then the second
     *
     * @param mixed $input
     * @param mixed $nextValue
     *
     * @return boolean
     */
    public function isHigher($input, $nextValue);

    /**
     * Returns whether the first value is lower then the second
     *
     * @param mixed $input
     * @param mixed $nextValue
     *
     * @return boolean
     */
    public function isLower($input, $nextValue);

    /**
     * Returns whether the first value equals then the second
     *
     * @param mixed $input
     * @param mixed $nextValue
     *
     * @return boolean
     */
    public function isEqual($input, $nextValue);

    /**
     * Returns whether the input value is legally formatted.
     *
     * Optionally the MessageBag can be used for adding multiple messages.
     * Set $message to null in that case.
     *
     * @param mixed        $input
     * @param string|array $message
     * @param MessageBag   $messageBag
     *
     * @return boolean
     */
    public function validateValue($input, &$message = null, MessageBag $messageBag = null);
}
