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

namespace Rollerworks\RecordFilterBundle\Formatter\Type;

use Rollerworks\RecordFilterBundle\Formatter\FilterTypeInterface;

/**
 * Decimal Formatter-validation type
 *
 * @author Sebastiaan Stok <s.stok@rollerscapes.net>
 *
 * @todo Filter extension instead of an Regex and detect proper decimal-sign
 */
class Decimal implements FilterTypeInterface
{
    /**
     * Sanitize the input string to an normal useful value
     *
     * @param string $input
     * @return string
     */
    public function sanitizeString($input)
    {
        return floatval(str_replace(',', '.', $input));
    }

    /**
     * Returns whether the first value is higher then the second
     *
     * @param string $input
     * @param string $nextValue
     * @return boolean
     */
    public function isHigher($input, $nextValue)
    {
        return ($input > $nextValue);
    }

    /**
     * Returns whether the first value is lower then the second
     *
     * @param string $input
     * @param string $nextValue
     * @return boolean
     */
    public function isLower($input, $nextValue)
    {
        return ($input < $nextValue);
    }

    /**
     * Returns whether the first value equals then the second
     *
     * @param string $input
     * @param string $nextValue
     * @return boolean
     */
    public function isEquals($input, $nextValue)
    {
        return ($input === $nextValue);
    }

    /**
     * Returns whether the input value is legally formatted
     *
     * @param string $input
     * @param string $message
     * @return boolean
     */
    public function validateValue($input, &$message=null)
    {
        $message = 'This value is not an valid decimal';

        if (!preg_match('#^[+-]?(([0-9]*[\.][0-9]+)|([0-9]+[\.][0-9]*))$#s', str_replace(',', '.', $input))) {
            return false;
        }
        else {
            return true;
        }
    }
}