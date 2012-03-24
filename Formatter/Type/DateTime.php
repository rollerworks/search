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

use Rollerworks\RecordFilterBundle\Formatter\ValueMatcherInterface;

/**
 * Time Formatter-validation type
 *
 * @author Sebastiaan Stok <s.stok@rollerscapes.net>
 */
class DateTime extends Time implements ValueMatcherInterface
{
    /**
     * Is the time-part optional
     *
     * @var bool
     */
    protected $timeOptional = false;

    /**
     * Constructor
     *
     * @param bool $time_optional
     */
    public function __construct($time_optional = false)
    {
        $this->timeOptional = $time_optional;
    }

    /**
     * Sanitize the input string to an normal useful value
     *
     * @param string $input
     * @return string
     */
    public function sanitizeString($input)
    {
        return DateTimeHelper::dateToISO($input);
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
        $message = 'This value is not an valid date with ' . ($this->timeOptional ? 'optional ' : '') . 'time';

        return DateTimeHelper::isDate($input, ($this->timeOptional ? 1 : true));
    }

    /**
     * {@inheritdoc}
     *
     * @return string
     */
    public function getRegex()
    {
        return '(?:\d{4}[-/. ]\d{1,2}[-/. ]\d{1,2}|\d{1,2}[-/. ]\d{1,2}[-/. ]\d{4}(?:(?:[T]|\s+)\d{1,2}[:.]\d{2}(?:[:.]\d{2})?(?:\s+[ap]m|(?:[+-]\d{1,2}(?:[:.]?\d{1,2})?))?)' . ($this->timeOptional ? '?' : '') .')';
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