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

/**
 * DateTime validation-type helper class
 *
 * @author Sebastiaan Stok <s.stok@rollerscapes.net>
 *
 * @todo Use DateTime extension
 */
class DateTimeHelper
{
    /**
     * Look if the given input is an legal date (with time).
     *
     * Accepts both European format and US.
     *
     * @param string          $input
     * @param bool|integer    $withTime
     * @return boolean
     */
    public static function isDate($input, $withTime = false)
    {
        if (!is_string($input)) {
            return false;
        }

        // http://www.w3.org/TR/NOTE-datetime
        // Look if there is an time-format

        // ISO-date:    (?:([0-9]{4})[-/ ](1[0-2]|[0]?[1-9])[-/ ](3[01]?|2[0-9]|[01]?[0-9]))
        // Euro-date:   (?:(3[01]?|2[0-9]|[01]?[0-9])[-/ ](1[0-2]|[0]?[1-9])[-/ ]([0-9]{4}))
        // Time:
        //              (?:(?:[01]?\d|2[0-3])[:.][0-5]\d(?:[:.][0-5]\d)?(?:\.\d+(?:-\d+)?)?)
        //              (?:(?:1[0-2]|0?[1-9])[:.][0-5]\d(?:[:.][0-5]\d)?\h*[aApP]\.?[mM]\.?)
        // Timezone:    (?:[+-](?:0[0-9]|1[012])(?:[:.]?[03]\d)?)

        if ($withTime && !preg_match('#^(?:(?:([0-9]{4})[-/. ](1[0-2]|[0]?[1-9])[-/. ](3[01]?|2[0-9]|[01]?[0-9]))|(?:(3[01]?|2[0-9]|[01]?[0-9])[-/. ](1[0-2]|[0]?[1-9])[-/. ]([0-9]{4})))(?:[ T](?:(?:(?:[01]?\d|2[0-3])[:.][0-5]\d(?:[:.][0-5]\d)?(?:[+-](?:0[0-9]|1[012])(?:[:.]?[03]\d)?)?(?:\.\d+(?:-\d+)?)?)|(?:(?:1[0-2]|0?[1-9]):[0-5]\d(?:[:.][0-5]\d)?\h*[aApP]\.?[mM]\.?)))'.($withTime === 1 ?  '?' : '').'$#s', $input, $dateMatch)) {
            return false;
        }
        elseif (!$withTime && !preg_match('#^(?:(?:([0-9]{4})[-/. ](1[0-2]|[0]?[1-9])[-/. ](3[01]?|2[0-9]|[01]?[0-9]))|(?:(3[01]?|2[0-9]|[01]?[0-9])[-/. ](1[0-2]|[0]?[1-9])[-/. ]([0-9]{4})))$#s', $input, $dateMatch)) {
            return false;
        }

        // MM-DD-YYYY
        // 5/4/6 EU
        // 2/3/1 US

        if (! empty($dateMatch[4])) {
            return \checkdate($dateMatch[5], $dateMatch[4], $dateMatch[6]);
        }
        else {
            return \checkdate($dateMatch[2], $dateMatch[3], $dateMatch[1]);
        }
    }

    /**
     * Format the input date to ISO date format.
     *
     * The output can be used directly in a database field.
     * Returns the same input if it is already in ISO format.
     *
     * Throws an exception on illegal input.
     *
     * @param string $input
     * @return string
     */
    public static function dateToISO($input)
    {
        if (!is_string($input)) {
            throw (new \InvalidArgumentException('Illegal input-type for dateToISO'));
        }

        $input = trim($input);

        // Already ISO format
        if (preg_match('#^[0-9]{4}-[0-9]{1,2}-[0-9]{1,2} \d{1,2}:\d{2}(:\d{2})?$#', $input)) {
            return $input;
        }

        // Already ISO format, but the separator is wrong
        if (preg_match('#^([0-9]{4})[-/. ]([0-9]{1,2})[-/. ]([0-9]{1,2})((?:[T]?|\h+)\d{1,2}[:.]\d{2}.*)?#', $input, $dateReformat)) {
            $return = $dateReformat[1] . '-' . sprintf('%02d', $dateReformat[2]) . '-' . sprintf('%02d', $dateReformat[3]);
        }
        elseif (preg_match('#^([0-9]{1,2})[-/. ]([0-9]{1,2})[-/. ]([0-9]{4})((?:[T]?|\h+)\d{1,2}[:.]\d{2}.*)?#', $input, $dateReformat)) {
            $return = sprintf('%02d', $dateReformat[3]) . '-' . sprintf('%02d', $dateReformat[2]) . '-' . $dateReformat[1];
        }
        else {
            throw (new \InvalidArgumentException('Unsupported format given for dateToISO()'));
        }

        if (! empty($dateReformat[4])) {
            $return .= ' ' . self::timeToISO(ltrim($dateReformat[4], 'T'));
        }

        return $return;
    }

    /**
     * Convert an human readable time to ISO.
     *
     * Converts am/pm to 24 hour, similar to dateToISO() but then only time.
     *
     * The input must be already validated.
     *
     * @param string  $input
     * @return string
     */
    public static function timeToISO($input)
    {
        $input = trim($input);

        if (preg_match('#^(\d{1,2})[:.](\d{2})([:.]\d{2})?([+-](?:0[0-9]|1[012])(?:[:.]?[03]\d)?)?$#', $input, $matches)) {
            $input = $matches[1] . ':' . $matches[2] . (isset($matches[3]) ? str_replace('.', ':', $matches[3]) : '');

            // Re-add timezone
            $input .= (! empty($matches[4]) ? $matches[4] : '');
        }
        elseif (preg_match('/^(?:(1[0-2]|0?[1-9])[:.]([0-5]\d)(?:[:.]([0-5]\d))?\h*([aApP])\.?[mM]\.?)$/', $input, $matches)) {
            if (!empty($matches[4]) && strtolower($matches[4]) == 'p') {
                $matches[1] = intval($matches[1]);

                if ($matches[1] == 12) {
                    $matches[1] = '00';
                }
                else {
                    $matches[1] += 12;
                }
            }

            $input = $matches[1] . ':' . $matches[2] . (!empty($matches[3]) ? ':' . $matches[3] : '');
        }
        else {
            throw (new \InvalidArgumentException('Unsupported format given for timeToISO()'));
        }

        return $input;
    }

    /**
     * Look if the given input is an legal time.
     *
     * Accepts pm/am (in all sorts of variations)
     *
     * @param string $input
     * @return boolean
     */
    public static function isTime($input)
    {
        if (!is_string($input)) {
            return false;
        }

        if (!preg_match('#^((?:(?:[01]?\d|2[0-3]):[0-5]\d(?:[:.][0-5]\d)?(?:[+-](?:0[0-9]|1[012])(?:[:.]?[03]\d)?)?(?:\.\d+(?:-\d+)?)?)|(?:(?:1[0-2]|0?[1-9]):[0-5]\d(?:[:.][0-5]\d)?\h*[aApP]\.?[mM]\.?))$#s', trim($input))) {
            return false;
        }
        else {
            return true;
        }
    }
}