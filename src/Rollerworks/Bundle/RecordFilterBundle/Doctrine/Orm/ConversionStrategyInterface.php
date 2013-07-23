<?php

/*
 * This file is part of the RollerworksRecordFilterBundle package.
 *
 * (c) Sebastiaan Stok <s.stok@rollerscapes.net>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Rollerworks\Bundle\RecordFilterBundle\Doctrine\Orm;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Types\Type as DBALType;

/**
 * ConversionStrategyInterface.
 *
 * Implement this interface to support the conversion strategy.
 *
 * The conversion strategy uses a different conversion 'output'
 * depending on the current value that is provided.
 *
 * @author Sebastiaan Stok <s.stok@rollerscapes.net>
 */
interface ConversionStrategyInterface
{
    /**
     * Returns the strategy index for the conversion using the value.
     *
     * This must be either return: null (default) or an integer.
     * Each strategy will use a different 'slot' in the query building.
     *
     * For example searching by age/birthday.
     * * If the value is DateTime, strategy 1 is used an the user-value is converted to date
     * * If the value is integer, strategy 2 is used and converts DB value to an age
     *
     * The conversion (field/value) gets the strategy by $parameters as '__conversion_strategy'.
     *
     * 0 is a special strategy, this will use the value as-is (only used for value conversion)
     * without using IN(), the field is provided as '__column' and is unconverted.
     *
     * With strategy 0 you must also implement the `CustomSqlValueConversionInterface::getConvertValuedSql()`
     * and return the full statement, example php: `return $parameters['__column'] . " <> $value"`
     *
     * @param mixed      $value
     * @param DBALType   $type
     * @param Connection $connection
     * @param array      $parameters
     *
     * @see CustomSqlValueConversionInterface
     *
     * @return null|integer
     */
    public function getConversionStrategy($value, DBALType $type, Connection $connection, array $parameters = array());
}
