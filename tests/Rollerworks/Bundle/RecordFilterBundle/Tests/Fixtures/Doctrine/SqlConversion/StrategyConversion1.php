<?php

/*
 * This file is part of the RollerworksRecordFilterBundle package.
 *
 * (c) Sebastiaan Stok <s.stok@rollerscapes.net>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Rollerworks\Bundle\RecordFilterBundle\Tests\Fixtures\Doctrine\SqlConversion;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Types\Type as DBALType;
use Rollerworks\Bundle\RecordFilterBundle\Doctrine\Orm\ConversionStrategyInterface;
use Rollerworks\Bundle\RecordFilterBundle\Doctrine\Orm\FieldConversionInterface;
use Rollerworks\Bundle\RecordFilterBundle\Doctrine\Orm\ValueConversionInterface;

class StrategyConversion1 implements ConversionStrategyInterface, FieldConversionInterface, ValueConversionInterface
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

        if ($value instanceof \DateTime || strpos($value, '-') !== false) {
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

        return "to_char('YYYY', age($fieldName))";
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
        return $value;
    }
}
