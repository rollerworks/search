<?php

/**
 * This file is part of RollerworksSearch Component package.
 *
 * (c) 2012-2014 Sebastiaan Stok <s.stok@rollerscapes.net>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Rollerworks\Component\Search\Extension\Doctrine\Dbal\Conversion;

use Doctrine\DBAL\Types\Type as DBALType;
use Rollerworks\Component\Search\Doctrine\Dbal\ConversionStrategyInterface;
use Rollerworks\Component\Search\Doctrine\Dbal\SqlFieldConversionInterface;
use Rollerworks\Component\Search\Doctrine\Dbal\ValueConversionInterface;
use Rollerworks\Component\Search\Exception\UnexpectedTypeException;

/**
 * AgeDateConversion.
 *
 * The chosen conversion strategy is done as follow.
 *
 * * 1: When the provided value is an integer, the DB-value is converted to an age.
 * * 2: When the provided value is an DateTime the input-value is converted to an date string.
 *
 * @author Sebastiaan Stok <s.stok@rollerscapes.net>
 */
class AgeDateConversion implements ConversionStrategyInterface, SqlFieldConversionInterface, ValueConversionInterface
{
    /**
     * Keep track of the connection state (SQLite only).
     *
     * This is used for SQLite to only register the custom function once.
     *
     * @var array
     */
    protected static $connectionState = array();

    /**
     * {@inheritDoc}
     */
    public function getConversionStrategy($value, array $options, array $hints)
    {
        if (!$value instanceof \DateTime && !ctype_digit((string) $value)) {
            throw new UnexpectedTypeException($value, '\DateTime object or integer');
        }

        if ($value instanceof \DateTime) {
            return 2;
        }

        return 1;
    }

    /**
     * {@inheritDoc}
     */
    public function convertSqlField($column, array $options, array $hints)
    {
        if (2 === $hints['conversion_strategy']) {
            return "CAST($column AS DATE)";
        }

        /** @var \Doctrine\DBAL\Connection $connection */
        $connection = $hints['connection'];

        switch ($connection->getDatabasePlatform()->getName()) {
            case 'postgresql':
                return "to_char('YYYY', age($column))";

            case 'mysql':
            case 'drizzle':
                return "(DATE_FORMAT(NOW(), '%Y') - DATE_FORMAT($column, '%Y') - (DATE_FORMAT(NOW(), '00-%m-%d') < DATE_FORMAT($column, '00-%m-%d')))";

            case 'mssql':
                return "DATEDIFF(hour, $column, GETDATE())/8766";

            case 'oracle':
                return "trunc((months_between(sysdate, (sysdate - $column)))/12)";

            // SQLite is a bit difficult, we must use a custom function
            // But we can only register this once.
            case 'sqlite':
                $conn = $connection->getWrappedConnection();
                $objHash = spl_object_hash($conn);
                if (!isset(self::$connectionState[$objHash])) {
                    $conn->sqliteCreateFunction('search_conversion_age', function ($date) {
                        return date_create($date)->diff(new \DateTime())->y;
                    }, 1);

                    self::$connectionState[$objHash] = true;
                }

                return "search_conversion_age($column)";

            default:
                throw new \RuntimeException(sprintf('Unsupported platform "%s" for AgeDateConversion.', $connection->getDatabasePlatform()->getName()));
        }
    }

    /**
     * {@inheritDoc}
     */
    public function requiresBaseConversion($input, array $options, array $hints)
    {
        return false;
    }

    /**
     * {@inheritDoc}
     */
    public function convertValue($value, array $options, array $hints)
    {
        if (2 === $hints['conversion_strategy']) {
            return DBALType::getType('date')->convertToDatabaseValue($value, $hints['connection']->getDatabasePlatform());
        }

        return (int) $value;
    }
}
