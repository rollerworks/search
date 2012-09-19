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

use \Rollerworks\Bundle\RecordFilterBundle\Doctrine\Sql\SqlValueConversionInterface;
use \Rollerworks\Bundle\RecordFilterBundle\Doctrine\Sql\SqlFieldConversionInterface;

class CustomerConversion implements SqlValueConversionInterface, SqlFieldConversionInterface
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
    public function convertValue($input, DBALType $type, Connection $connection, $isDql, array $params = array())
    {
        return $input->getCustomerId();
    }

    /**
     * Convert the input field name to an SQL statement.
     *
     * This should return the field wrapped inside an statement like: MY_FUNCTION(fieldName)
     *
     * @param string     $fieldName
     * @param DBALType   $type
     * @param Connection $connection
     * @param boolean    $isDql      Whether the query should be DQL
     *
     * @return string
     */
    public function convertField($fieldName, DBALType $type, Connection $connection, $isDql)
    {
        return "CAST($fieldName AS customer_type)";
    }
}
