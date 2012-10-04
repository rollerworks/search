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
     * Convert the input to save SQL statement.
     *
     * Input value is as-is and must be returned quoted when this required.
     *
     * @param mixed      $input
     * @param DBALType   $type
     * @param Connection $connection
     * @param boolean    $isDql      Whether the query should be DQL
     * @param array      $parameters
     *
     * @return mixed
     */
    public function convertValue($input, DBALType $type, Connection $connection, $isDql, array $parameters = array());
}
