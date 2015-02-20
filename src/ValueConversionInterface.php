<?php

/*
 * This file is part of the RollerworksSearch Component package.
 *
 * (c) Sebastiaan Stok <s.stok@rollerscapes.net>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Rollerworks\Component\Search\Doctrine\Dbal;

/**
 * ValueConversionInterface provides a value conversion for the SQL generating process.
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
     * @param string          $input   Input value
     * @param array           $options Options of the Field configuration
     * @param ConversionHints $hints   Special information for the conversion process
     *
     * @return bool
     */
    public function requiresBaseConversion($input, array $options, ConversionHints $hints);

    /**
     * Returns the converted input.
     *
     * @param mixed           $input   Input value
     * @param array           $options Options of the Field configuration
     * @param ConversionHints $hints   Special information for the conversion process
     *
     * @return string
     */
    public function convertValue($input, array $options, ConversionHints $hints);
}
