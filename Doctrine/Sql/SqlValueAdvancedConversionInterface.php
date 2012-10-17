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
 * SqlValueAdvancedConversionInterface.
 *
 * An SQL value conversion class must may implement this class as replace for SqlValueConversionInterface.
 * This interface allows wrapping the value inside an SQL function for further conversion.
 *
 * @author Sebastiaan Stok <s.stok@rollerscapes.net>
 */
interface SqlValueAdvancedConversionInterface extends SqlValueConversionInterface
{
    /**
     * Returns the $input wrapped inside an SQL function like my_func($input).
     *
     * The input is either an named parameter or loose value.
     *
     * @param string     $input
     * @param DBALType   $type
     * @param Connection $connection
     * @param array      $parameters
     *
     * @return string
     */
    public function getConvertValuedSql($input, DBALType $type, Connection $connection, array $parameters = array());
}
