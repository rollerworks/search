<?php

/**
 * This file is part of RollerworksSearch Component package.
 *
 * (c) 2012-2014 Sebastiaan Stok <s.stok@rollerscapes.net>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Rollerworks\Component\Search\Doctrine\Dbal;

/**
 * SqlValueConversionInterface provides a value conversion (as SQL statement) for the SQL generating process.
 *
 * The $hints parameter always receives the following information.
 *
 * * search_field: Rollerworks\Component\Search\FieldConfigInterface
 * * connection: Doctrine\DBAL\Connection
 * * db_type: Doctrine\DBAL\Types\Type
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
     * @param array $hints   Special information for the conversion process (search_field, connection, db_type, value_object)
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
     * @param array $hints   Special information for the conversion process (search_field, connection, db_type)
     *
     * @return string
     */
    public function convertSqlValue($input, array $options, array $hints);
}
