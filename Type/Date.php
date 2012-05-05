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
use Rollerworks\RecordFilterBundle\Type\ValueMatcherInterface;
use Rollerworks\RecordFilterBundle\Formatter\ValuesToRangeInterface;
use Rollerworks\RecordFilterBundle\Value\SingleValue;

/**
 * Date Formatter-validation type
 *
 * @author Sebastiaan Stok <s.stok@rollerscapes.net>
 */
class Date implements FilterTypeInterface, ValueMatcherInterface, ValuesToRangeInterface
{
    /**
     * {@inheritdoc}
     */
    public function sanitizeString($input)
    {
        return DateTimeHelper::dateToISO($input);
    }

    /**
     * {@inheritdoc}
     */
    public function formatOutput($value)
    {

    }

    /**
     * Get timestamp of an value
     *
     * @param string $date
     * @return integer
     */
    protected function getTimestamp($date)
    {
        $date = new \DateTime($date);

        return $date->getTimestamp();
    }

    /**
     * {@inheritdoc}
     */
    public function isHigher($input, $nextValue)
    {
        return ($this->getTimestamp($input) > $this->getTimestamp($nextValue));
    }

    /**
     * {@inheritdoc}
     */
    public function isLower($input, $nextValue)
    {
        return ($this->getTimestamp($input) < $this->getTimestamp($nextValue));
    }

    /**
     * {@inheritdoc}
     */
    public function isEquals($input, $nextValue)
    {
        return ($this->getTimestamp($input) === $this->getTimestamp($nextValue));
    }

    /**
     * {@inheritdoc}
     */
    public function validateValue($input, &$message = null)
    {
        $message = 'This value is not a valid date';

        return DateTimeHelper::isDate($input, false);
    }

    /**
     * {@inheritdoc}
     */
    public function getRegex()
    {
        return '(?:\d{4}[-/. ]\d{1,2}[-/. ]\d{1,2}|\d{1,2}[-/. ]\d{1,2}[-/. ]\d{4})';
    }

    /**
     * {@inheritdoc}
     */
    public function sortValuesList(SingleValue $first, SingleValue $second)
    {
        $a = $this->getTimestamp($first->getValue());
        $b = $this->getTimestamp($second->getValue());

        if ($a == $b) {
            return 0;
        }

        return $a < $b ? -1 : 1;
    }

    /**
     * {@inheritdoc}
     */
    public function getHigherValue($input)
    {
        $date = new \DateTime($input);
        $date->modify('+1 day');

        return $date->format('Y-m-d');
    }
}
