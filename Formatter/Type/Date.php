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