<?php

/**
 * This file is part of the RollerworksSearch Component package.
 *
 * (c) 2012-2014 Sebastiaan Stok <s.stok@rollerscapes.net>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Rollerworks\Component\Search\Doctrine\Dbal;

/**
 * ValueConversionInterface provides a value conversion for the SQL generating process.
 *
 * The $hints parameter always receives the following information.
 *
 * * search_field: Rollerworks\Component\Search\FieldConfigInterface
 * * connection: Doctrine\DBAL\Connection
 * * db_type: Doctrine\DBAL\Types\Type
 *
 * Value conversion only:
 * * value_object: The value object currently being processed (SingleValue, Range, Compare, PatternMatch).
 *
 * @author Sebastiaan Stok <s.stok@rollerscapes.net>
 */
interface ValueConversionInterface
{
    /**
     * Returns whether the base-conversion of the value is required.
     *
     * The base conversion uses the convertToDatabaseValue()
     * of the database type.
     *
     * The original value is still available
     * with originalValue in the $hints parameter
     *
     * @param string $input   Input value
     * @param array  $options Options of the Field configuration
     * @param array  $hints   Special information for the conversion process
     *                        (search_field, connection, db_type, value_object)
     *
     * @return bool
     */
    public function requiresBaseConversion($input, array $options, array $hints);

    /**
     * Returns the converted input.
     *
     * @param mixed $input   Query-parameter reference or real input depending on requiresRealValue()
     * @param array $options Options of the Field configuration
     * @param array $hints   Special information for the conversion process
     *                       (search_field, connection, db_type, original_value, value_object)
     *
     * @return string
     */
    public function convertValue($input, array $options, array $hints);
}
