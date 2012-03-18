<?php

/**
 * This file is part of the RollerworksRecordFilterBundle.
 *
 * (c) Rollerscapes
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @link    http://projects.rollerscapes.net/RollerFramework
 * @license http://www.opensource.org/licenses/lgpl-license.php LGPL
 */

namespace Rollerworks\RecordFilterBundle\Formatter\Type;

use Rollerworks\RecordFilterBundle\Formatter\FilterType;

/**
 * Decimal Formatter-validation type
 *
 * @author Sebastiaan Stok <s.stok@rollerscapes.net>
 *
 * @todo Filter extension instead of an Regex and detect proper decimal-sign
 */
class Decimal implements FilterType
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