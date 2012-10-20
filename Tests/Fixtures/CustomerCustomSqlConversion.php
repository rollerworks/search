<?php

/**
 * This file is part of the RollerworksRecordFilterBundle.
 *
 * (c) Sebastiaan Stok <s.stok@rollerscapes.net>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Rollerworks\Bundle\RecordFilterBundle\Tests\Fixtures;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Types\Type as DBALType;
use Rollerworks\Bundle\RecordFilterBundle\Doctrine\Orm\CustomSqlValueConversionInterface;
use Rollerworks\Bundle\RecordFilterBundle\Doctrine\Orm\FieldConversionInterface;

class CustomerCustomSqlConversion implements CustomSqlValueConversionInterface, FieldConversionInterface
{
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
    public function convertValue($input, DBALType $type, Connection $connection, array $params = array())
    {
        return intval($input->getCustomerId());
    }

    /**
     * {@inheritdoc}
     */
    public function getConvertFieldSql($fieldName, DBALType $type, Connection $connection, array $params = array())
    {
        return $fieldName;
    }

    /**
     * Returns the $input wrapped inside an SQL function like my_func($input).
     *
     * The input is either an named parameter or loose value.
     * An loose value is already quoted and should be used as-is.
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
        if ($parameters) {
            return "get_customer_type($input, '" . json_encode($parameters) . "')";
        }

        return "get_customer_type($input)";
    }
}
