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
use Rollerworks\Bundle\RecordFilterBundle\Doctrine\Orm\CustomSqlValueConversionInterface;
use Rollerworks\Bundle\RecordFilterBundle\Doctrine\Orm\FieldConversionInterface;
use Rollerworks\Bundle\RecordFilterBundle\Doctrine\Orm\ValueConversionInterface;

class StrategyConversion2 implements ConversionStrategyInterface, ValueConversionInterface, CustomSqlValueConversionInterface
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
        if ($value->getCustomerId() > 5) {
            return 0;
        }

        return 1;
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
        return $value->getCustomerId();
    }

    /**
     * Returns the $input wrapped inside an SQL function like my_func($input).
     *
     * The input is either a named parameter or loose value.
     * A loose value is already quoted and should be used as-is.
     *
     * @param string     $input
     * @param DBALType   $type
     * @param Connection $connection
     * @param array      $parameters
     *
     * @return string
     */
    public function getConvertValuedSql($input, DBALType $type, Connection $connection, array $parameters = array())
    {
        if (0 === $parameters['__conversion_strategy']) {
            return $parameters['__column'] . " ~ $input";
        }

        return $input;
    }
}
