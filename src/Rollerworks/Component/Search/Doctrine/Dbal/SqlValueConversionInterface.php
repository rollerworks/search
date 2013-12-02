<?php

/*
 * This file is part of the Rollerworks Search Component package.
 *
 * (c) Sebastiaan Stok <s.stok@rollerscapes.net>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Rollerworks\Component\Search\Doctrine\Dbal;

use Rollerworks\Component\Search\Doctrine\Dbal\ValueConversionInterface;

/**
 * SqlValueConversionInterface provides a value conversion (as SQL statement) for the SQL generating process.
 *
 * The $hints parameter always receives the following information.
 *
 * * searchField: Rollerworks\Component\Search\FieldConfigInterface
 * * connection: Doctrine\DBAL\Connection
 * * dbType: Doctrine\DBAL\Types\Type
 *
 * @author Sebastiaan Stok <s.stok@rollerscapes.net>
 */
interface SqlValueConversionInterface extends ValueConversionInterface
{
    /**
     * Returns whether the value must embedded with the statement.
     *
     * @param mixed $input   Input value
     * @param array $options Options of the Field configuration
     * @param array $hints   Special information for the conversion process (searchField, connection, dbType, valueObject)
     *
     * @return boolean Return true to receive the value as-is, false to receive the query-param name.
     */
    public function valueRequiresEmbedding($input, array $options, array $hints);

    /**
     * Returns the converted input as SQL statement.
     *
     * Caution: The result of this method is used as-is,
     * so its important to escape any values used in the returned statement.
     *
     * @param mixed $input   Query-parameter reference or real input depending on requiresRealValue()
     * @param array $options Options of the Field configuration
     * @param array $hints   Special information for the conversion process (searchField, connection, dbType)
     *
     * @return string
     */
    public function convertSqlValue($input, array $options, array $hints);
}
