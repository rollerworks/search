<?php

/*
 * This file is part of the Rollerworks Search Component package.
 *
 * (c) Sebastiaan Stok <s.stok@rollerscapes.net>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Rollerworks\Component\Search\Exception;

/**
 * UnsupportedValueTypeException.
 *
 * Throw this exception when the value-type is not supported for the field.
 */
class UnsupportedValueTypeException extends \RuntimeException
{
    protected $fieldName;
    protected $valueType;

    /**
     * @param string $fieldName
     * @param string $valueType
     */
    public function __construct($fieldName, $valueType)
    {
        $this->fieldName = $fieldName;
        $this->valueType = $valueType;

        parent::__construct(sprintf('Field "%s" does accept %s values.', $fieldName, $valueType));
    }
}
