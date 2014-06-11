<?php

/**
 * This file is part of RollerworksSearch Component package.
 *
 * (c) 2012-2014 Sebastiaan Stok <s.stok@rollerscapes.net>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Rollerworks\Component\Search\Extension\Core\Model;

/**
 * @author Sebastiaan Stok <s.stok@rollerscapes.net>
 */
class MoneyValue
{
    public $currency;
    public $value;

    /**
     * Constructor.
     *
     * @param string       $currency
     * @param string|float $value
     */
    public function __construct($currency, $value)
    {
        $this->currency = $currency;
        $this->value = $value;
    }
}
