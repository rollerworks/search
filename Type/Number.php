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
use Rollerworks\RecordFilterBundle\Formatter\ValuesToRangeInterface;

use Rollerworks\RecordFilterBundle\Value\SingleValue;

/**
 * Integer Formatter-validation type
 *
 * @author Sebastiaan Stok <s.stok@rollerscapes.net>
 */
class Number implements FilterTypeInterface, ValuesToRangeInterface
{
    /**
     * {@inheritdoc}
     */
    public function sanitizeString($input)
    {
        return intval($input);
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
    public function validateValue($input, &$message = null)
    {
        $message = 'This value is no valid integer';

        if (!preg_match('#^[+-]?([1-9][0-9]*|0)$#s', (string) $input)) {
            return false;
        }

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function sortValuesList(SingleValue $first, SingleValue $second)
    {
        if ($first->getValue() == $second->getValue()) {
            return 0;
        }

        return ($first->getValue() < $second->getValue()) ? -1 : 1;
    }

    /**
     * {@inheritdoc}
     */
    public function getHigherValue($input)
    {
        return $input + 1;
    }
}