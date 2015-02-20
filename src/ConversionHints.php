<?php

/**
 * PhpStorm.
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
