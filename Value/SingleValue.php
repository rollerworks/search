<?php

/**
 * This file is part of the RollerworksRecordFilterBundle.
 *
 * (c) Sebastiaan Stok <s.stok@rollerscapes.net>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Rollerworks\RecordFilterBundle\Value;

/**
 * Single value
 */
class SingleValue
{
    /**
     * @var mixed
     */
    protected $value;

    /**
     * @var mixed
     */
    protected $originalValue;

    /**
     * Constructor
     *
     * @param mixed $value
     * @param mixed $original
     *
     * @throws \UnexpectedValueException
     *
     * @api
     */
    public function __construct($value, $original = null)
    {
        if (!is_string($value) && !is_float($value) && !is_integer($value)) {
            throw new \UnexpectedValueException('Value type is not accepted, only string, float and integer are accepted.');
        }

        if (is_null($original)) {
            $original = $value;
        }

        $this->value = $value;
        $this->originalValue = $original;
    }

    /**
     * Set/overwrite the singe value
     *
     * @param mixed $value
     *
     * @throws \UnexpectedValueException
     *
     * @api
     */
    public function setValue($value)
    {
        if (!is_string($value) && !is_float($value) && !is_integer($value)) {
            throw new \UnexpectedValueException('Value type is not accepted, only string, float and integer are accepted.');
        }

        $this->value = $value;
    }

    /**
     * Get the single value
     *
     * @return mixed
     *
     * @api
     */
    public function getValue()
    {
        return $this->value;
    }

    /**
     * Get the original loose value
     *
     * @return mixed
     *
     * @api
     */
    public function getOriginalValue()
    {
        return $this->originalValue;
    }

    /**
     * @return string
     *
     * @deprecated The type is mixed and thus not an value
     */
    public function __toString()
    {
        return $this->value;
    }
}
