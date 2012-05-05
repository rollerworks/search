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

use Rollerworks\RecordFilterBundle\Type\FilterTypeInterface;

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
     * {@inheritdoc}
     */
    public function sanitizeString($input)
    {
        return floatval(str_replace(',', '.', $input));
    }

    /**
     * {@inheritdoc}
     */
    public function formatOutput($value)
    {

    }

    /**
     * {@inheritdoc}
     */
    public function isHigher($input, $nextValue)
    {
        return ($input > $nextValue);
    }

    /**
     * {@inheritdoc}
     */
    public function isLower($input, $nextValue)
    {
        return ($input < $nextValue);
    }

    /**
     * {@inheritdoc}
     */
    public function isEquals($input, $nextValue)
    {
        return ($input === $nextValue);
    }

    /**
     * {@inheritdoc}
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
