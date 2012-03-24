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

use Rollerworks\RecordFilterBundle\Formatter\FilterType;
use Rollerworks\RecordFilterBundle\Formatter\ValueMatcherInterface;

/**
 * Date Formatter-validation type
 *
 * @author Sebastiaan Stok <s.stok@rollerscapes.net>
 */
class Date implements FilterType, ValueMatcherInterface
{
    /**
     * Sanitize the input string to an normal useful value.
     * This will format the output to: YYYY-MM-DD
     *
     * @param string $input
     * @return string
     */
    public function sanitizeString($input)
    {
        return DateTimeHelper::dateToISO($input);
    }

    /**
     * Get timestamp of an value
     *
     * @param string $psTime
     * @return integer
     */
    protected function getTimestamp($psTime)
    {
        $date = new \DateTime($psTime);

        return $date->getTimestamp();
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
        return ($this->getTimestamp($input) > $this->getTimestamp($nextValue));
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
        return ($this->getTimestamp($input) < $this->getTimestamp($nextValue));
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
        return ($this->getTimestamp($input) === $this->getTimestamp($nextValue));
    }

    /**
     * Returns whether the input value is legally formatted
     *
     * @param string $input
     * @param string $message
     * @return boolean
     */
    public function validateValue($input, &$message = null)
    {
        $message = 'This value is not a valid date';

        return DateTimeHelper::isDate($input, false);
    }

    /**
     * {@inheritdoc}
     *
     * @return string
     */
    public function getRegex()
    {
        return '(?:\d{4}[-/. ]\d{1,2}[-/. ]\d{1,2}|\d{1,2}[-/. ]\d{1,2}[-/. ]\d{4})';
    }

    /**
     * {@inheritdoc}
     *
     * @return bool
     */
    public function supportsJs()
    {
        return true;
    }
}