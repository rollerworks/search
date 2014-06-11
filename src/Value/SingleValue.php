<?php

/**
 * This file is part of the RollerworksSearch Component package.
 *
 * (c) 2012-2014 Sebastiaan Stok <s.stok@rollerscapes.net>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Rollerworks\Component\Search\Value;

/**
 * @author Sebastiaan Stok <s.stok@rollerscapes.net>
 */
class SingleValue
{
    /**
     * @var mixed
     */
    protected $value;

    /**
     * @var string
     */
    protected $viewValue;

    /**
     * Constructor.
     *
     * @param mixed $value
     * @param mixed $viewValue
     */
    public function __construct($value, $viewValue = null)
    {
        $this->value = $value;
        $this->viewValue = null !== $viewValue ? $viewValue : $value;;
    }

    /**
     * @param mixed $value
     */
    public function setValue($value)
    {
        $this->value = $value;
    }

    /**
     * @return mixed
     */
    public function getValue()
    {
        return $this->value;
    }

    /**
     * @param string $value
     */
    public function setViewValue($value)
    {
        $this->viewValue = $value;
    }

    /**
     * @return string
     */
    public function getViewValue()
    {
        return $this->viewValue;
    }
}
