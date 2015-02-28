<?php

/*
 * This file is part of the RollerworksSearch package.
 *
 * (c) Sebastiaan Stok <s.stok@rollerscapes.net>
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
    /**
     * @var string
     */
    public $currency;

    /**
     * @var float
     */
    public $value;

    /**
     * Constructor.
     *
     * @param string $currency
     * @param float  $value
     */
    public function __construct($currency, $value)
    {
        $this->currency = $currency;
        $this->value = $value;
    }
}
