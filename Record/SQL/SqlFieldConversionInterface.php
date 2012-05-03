<?php

/**
 * This file is part of the RollerworksRecordFilterBundle.
 *
 * (c) Sebastiaan Stok <s.stok@rollerscapes.net>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Rollerworks\RecordFilterBundle\Record\Sql;

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
     * @param string                    $fieldName
     * @param \Doctrine\DBAL\Types\Type $type
     * @param \Doctrine\DBAL\Connection $connection
     * @param boolean                   $isDql Whether the query should be DQL
     * @return string
     */
    public function convertField($fieldName, \Doctrine\DBAL\Types\Type $type, \Doctrine\DBAL\Connection $connection, $isDql);
}
