<?php

/**
 * This file is part of the RollerworksRecordFilterBundle.
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
 * CustomSqlValueConversionInterface.
 *
 * An SQL value conversion class may implement this class as a replace for the ValueConversionInterface.
 * This interface allows wrapping the value inside an SQL function for further conversion.
 *
 * @author Sebastiaan Stok <s.stok@rollerscapes.net>
 */
interface CustomSqlValueConversionInterface extends ValueConversionInterface
{
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
    public function getConvertValuedSql($input, DBALType $type, Connection $connection, array $parameters = array());
}
