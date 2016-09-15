<?php

/*
 * This file is part of the RollerworksSearch package.
 *
 * (c) Sebastiaan Stok <s.stok@rollerscapes.net>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Rollerworks\Component\Search\Value;

/**
 * @author Sebastiaan Stok <s.stok@rollerscapes.net>
 *
 * @deprecated Deprecated since version 1.2, to be removed in 2.0.
 *             Simple values will be no longer kept inside an object
 */
final class SingleValue
{
    /**
     * @var mixed
     */
    private $value;

    /**
     * @var string
     */
    private $viewValue;

    /**
     * Constructor.
     *
     * @param mixed  $value
     * @param string $viewValue
     */
    public function __construct($value, $viewValue = null)
    {
        $this->value = $value;
        $this->viewValue = (string) (null !== $viewValue ? $viewValue : $value);
    }

    /**
     * @return mixed
     */
    public function getValue()
    {
        return $this->value;
    }

    /**
     * @return string
     */
    public function getViewValue()
    {
        return $this->viewValue;
    }
}
