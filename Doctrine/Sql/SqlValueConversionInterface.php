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
 * SqlValueConversionInterface.
 *
 * An SQL value conversion class must implement this interface.
 *
 * @author Sebastiaan Stok <s.stok@rollerscapes.net>
 */
interface SqlValueConversionInterface
{
    /**
     * Returns whether the base-conversion of the field-type is requires.
     *
     * If anything but true is returned its not performed.
     *
     * @return boolean
     */
    public function requiresBaseConversion();

    /**
     * Convert the value for usage.
     *
     * The value will be either used as parameter value or as-is.
     * An string value will ALWAYS be quoted.
     *
     * @param mixed      $value
     * @param DBALType   $type
     * @param Connection $connection
     * @param array      $parameters
     *
     * @return string|float|integer scalar value
     */
    public function convertValue($value, DBALType $type, Connection $connection, array $parameters = array());
}
