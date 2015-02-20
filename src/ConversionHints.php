<?php

/*
 * This file is part of the RollerworksSearch Component package.
 *
 * (c) Sebastiaan Stok <s.stok@rollerscapes.net>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Rollerworks\Component\Search\Doctrine\Dbal;

use Doctrine\DBAL\Connection;
use Rollerworks\Component\Search\Doctrine\Dbal\Query\QueryField;

class ConversionHints
{
    /**
     * @var QueryField
     */
    public $field;

    /**
     * @var Connection
     */
    public $connection;

    /**
     * @var null|int
     */
    public $conversionStrategy;

    /**
     * @var string
     */
    public $column;
}
