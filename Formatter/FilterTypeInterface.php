<?php

/**
 * This file is part of the RollerworksRecordFilterBundle.
 *
 * (c) Rollerscapes
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU Lesser General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Lesser General Public License for more details.
 *
 * You should have received a copy of the GNU Lesser General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 * @link    http://projects.rollerscapes.net/RollerFramework
 * @license http://www.opensource.org/licenses/lgpl-license.php LGPL
 */

namespace Rollerworks\RecordFilterBundle\Formatter;

/**
 * Filter field value-type interface.
 *
 * Each field value-type must implement this interface.
 * The input for comparing values is always sanitized.
 *
 * @author Sebastiaan Stok <s.stok@rollerscapes.net>
 */
interface FilterTypeInterface
{
    /**
     * Sanitize the input string to an normal useful value
     *
     * @param $input
     * @return string
     */
    public function sanitizeString($input);

    /**
     * Returns whether the first value is higher then the second
     *
     * @param string $input
     * @param string $nextValue
     * @return boolean
     */
    public function isHigher($input, $nextValue);

    /**
     * Returns whether the first value is lower then the second
     *
     * @param string $input
     * @param string $nextValue
     * @return boolean
     */
    public function isLower($input, $nextValue);

    /**
     * Returns whether the first value equals then the second
     *
     * @param string $input
     * @param string $nextValue
     * @return boolean
     */
    public function isEquals($input, $nextValue);

    /**
     * Returns whether the input value is legally formatted
     *
     * @param string $input
     * @param string $message
     * @return boolean
     */
    public function validateValue($input, &$message = null);
}