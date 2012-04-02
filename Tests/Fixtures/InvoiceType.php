<?php

/**
 * This file is part of the RollerworksRecordFilterBundle.
 *
 * (c) Sebastiaan Stok <s.stok@rollerscapes.net>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Rollerworks\RecordFilterBundle\Tests\Fixtures;

use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

use Rollerworks\RecordFilterBundle\Formatter\FilterTypeInterface;
use Rollerworks\RecordFilterBundle\Formatter\ValueMatcherInterface;
use Rollerworks\RecordFilterBundle\FilterStruct;

class InvoiceType implements FilterTypeInterface, ValueMatcherInterface, ContainerAwareInterface
{
    public function setContainer(ContainerInterface $container = null)
    {
    }

    /**
     * Sanitize the input string to an normal useful value
     *
     * @param string $input
     * @return string
     */
    public function sanitizeString($input)
    {
        return $input;
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
        return false;
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
        return true;
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
    public function validateValue($input, &$message = null)
    {
        $message = 'This is not an valid invoice';

        return (preg_match('/^F?\d{4}-\d+$/i', $this->sanitizeString($input)) ? true : false );
    }

    /**
     * Returns the regex (without delimiters).
     *
     * @return string
     */
    public function getRegex()
    {
        return '(?:F\d{4}-\d+)';
    }

    /**
     * Returns true
     *
     * @return bool
     */
    public function supportsJs()
    {
        return true;
    }
}