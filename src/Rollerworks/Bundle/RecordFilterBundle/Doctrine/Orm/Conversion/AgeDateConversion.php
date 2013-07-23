<?php

/**
 * This file is part of the RollerworksRecordFilterBundle.
 *
 * (c) Sebastiaan Stok <s.stok@rollerscapes.net>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Rollerworks\Bundle\RecordFilterBundle\Doctrine\Orm\Conversion;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Types\Type as DBALType;
use Rollerworks\Bundle\RecordFilterBundle\Doctrine\Orm\ConversionStrategyInterface;
use Rollerworks\Bundle\RecordFilterBundle\Doctrine\Orm\FieldConversionInterface;
use Rollerworks\Bundle\RecordFilterBundle\Doctrine\Orm\ValueConversionInterface;

/**
 * AgeDateConversion.
 *
 * The chosen conversion strategy is done as follow.
 *
 *  * 1: When the provided value is an integer, the DB-value is converted to an age.
 *  *    When the provided value is an DateTime:
 *        2 if the DB-value is an Date the input-value is converted to an date string.
 *        3 if the DB-value is an DateTime the DB-value is casted to an Date and input-value converted to an date string.
 *
 * @author Sebastiaan Stok <s.stok@rollerscapes.net>
 */
class AgeDateConversion implements ConversionStrategyInterface, FieldConversionInterface, ValueConversionInterface
{
    /**
     * @var array
     */
    protected static $connectionState = array();

    /**
     * {@inheritdoc}
     */
    public function getConversionStrategy($value, DBALType $type, Connection $connection, array $parameters = array())
    {
        if (is_object($value) && !$value instanceof \DateTime) {
            throw new \InvalidArgumentException('Only integer/string and DateTime are accepted.');
        }

        if ($value instanceof \DateTime) {
            if ('datetime' === $type->getName()) {
                return 3;
            }

            return 2;
        }

        return 1;
    }

    /**
     * {@inheritdoc}
     */
    public function getConvertFieldSql($fieldName, DBALType $type, Connection $connection, array $parameters = array())
    {
        if ($parameters['__conversion_strategy'] > 1) {
            if (3 === $parameters['__conversion_strategy']) {
                return "CAST($fieldName AS DATE)";
            }

            return $fieldName;
        }

        switch ($connection->getDatabasePlatform()->getName()) {
            case 'postgresql':
                return "to_char('YYYY', age($fieldName))";
                break;

            case 'mysql':
            case 'drizzle':
                return "(DATE_FORMAT(NOW(), '%Y') - DATE_FORMAT($fieldName, '%Y') - (DATE_FORMAT(NOW(), '00-%m-%d') < DATE_FORMAT($fieldName, '00-%m-%d')))";
                break;

            case 'mssql':
                return "DATEDIFF(hour, $fieldName, GETDATE())/8766";
                break;

            case 'oracle':
                return "trunc((months_between(sysdate, (sysdate - $fieldName)))/12)";
                break;

            // SQLite is a bit difficult, we must use a custom function
            // But can only register this once.
            case 'sqlite':
                $conn = $connection->getWrappedConnection();
                $objHash = spl_object_hash($conn);
                if (!isset(self::$connectionState[$objHash])) {
                    $conn->sqliteCreateFunction('record_filter_age', function ($date) {
                        return date_create($date)->diff(new \DateTime())->y;
                    }, 1);

                    self::$connectionState[$objHash] = true;
                }

                return "record_filter_age($fieldName)";
                break;

            default:
                throw new \RuntimeException(sprintf('Unsupported platform "%s" for AgeDateConversion.', $connection->getDatabasePlatform()->getName()));
        }
    }

    /**
     * {@inheritdoc}
     */
    public function requiresBaseConversion()
    {
        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function convertValue($value, DBALType $type, Connection $connection, array $parameters = array())
    {
        if ($parameters['__conversion_strategy'] > 1) {
            /** @var \DateTime $value */
            switch ($parameters['__conversion_strategy']) {
                case 3:
                    return DBALType::getType('date')->convertToDatabaseValue($value, $connection->getDatabasePlatform());
                    break;

                case 2:
                    return $type->convertToDatabaseValue($value, $connection->getDatabasePlatform());
                    break;
            }
        }

        return $value;
    }
}
