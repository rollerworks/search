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
     * @return mixed
     */
    public function sanitizeString($input);

    /**
     * Formats the value for display and return it as a string.
     *
     * This function does the opposite of sanitizeString().
     *
     * @param mixed $value
     * @return string
     */
    public function formatOutput($value);

    /**
     * Returns whether the first value is higher then the second
     *
     * @param mixed $input
     * @param mixed $nextValue
     * @return boolean
     */
    public function isHigher($input, $nextValue);

    /**
     * Returns whether the first value is lower then the second
     *
     * @param mixed $input
     * @param mixed $nextValue
     * @return boolean
     */
    public function isLower($input, $nextValue);

    /**
     * Returns whether the first value equals then the second
     *
     * @param mixed $input
     * @param mixed $nextValue
     * @return boolean
     */
    public function isEquals($input, $nextValue);

    /**
     * Returns whether the input value is legally formatted
     *
     * @param mixed $input
     * @param mixed $message
     * @return boolean
     */
    public function validateValue($input, &$message = null);
}
