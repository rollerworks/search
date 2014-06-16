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
 * ConversionStrategyInterface, allows for different conversion strategies.
 *
 * @author Sebastiaan Stok <s.stok@rollerscapes.net>
 */
interface ConversionStrategyInterface
{
    /**
     * Returns the conversion strategy.
     *
     * This must either return: null (default) or an integer.
     * Each strategy will use a different 'slot' during the query building.
     *
     * For example searching by age/birthday.
     * * If the value is a DateTime object, strategy 1 is used and the input-value is converted to a date string.
     * * If the value is an integer, strategy 2 is used and the value is transformed using a custom SQL statement.
     *
     * The converter will receive the strategy as 'conversion_strategy' in the $hints parameter
     *
     * @param mixed $value   Input value
     * @param array $options Options of the Field configuration
     * @param array $hints   Special information for the conversion process
     *                       (searchField, connection, dbType, valueObject)
     *
     * @return null|int The determined strategy
     */
    public function getConversionStrategy($value, array $options, array $hints);
}
