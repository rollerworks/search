<?php

/**
 * This file is part of the RollerworksRecordFilterBundle.
 *
 * (c) Sebastiaan Stok <s.stok@rollerscapes.net>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Rollerworks\Bundle\RecordFilterBundle\Doctrine\Sql;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Types\Type as DBALType;

/**
 * SqlFieldConversionInterface.
 *
 * An SQL field conversion class must implement this interface.
 *
 * @author Sebastiaan Stok <s.stok@rollerscapes.net>
 */
interface SqlFieldConversionInterface
{
    /**
     * Convert the input field name to an SQL statement.
     *
     * This should return the field wrapped inside an statement like: MY_FUNCTION(fieldName)
     *
     * @param string     $fieldName
     * @param DBALType   $type
     * @param Connection $connection
     *
     * @return string
     */
    public function getConvertFieldSql($fieldName, DBALType $type, Connection $connection);
}
