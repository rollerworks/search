<?php

/*
 * This file is part of the Rollerworks Search Component package.
 *
 * (c) Sebastiaan Stok <s.stok@rollerscapes.net>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Rollerworks\Component\Search\Extension\Core\Model;

class MoneyValue
{
    public $currency;

    public $value;

    public function __construct($currency, $value)
    {
        $this->currency = $currency;
        $this->value = $value;
    }
}
