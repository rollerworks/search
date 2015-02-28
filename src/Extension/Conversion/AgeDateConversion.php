<?php

/*
 * This file is part of the RollerworksSearch Component package.
 *
 * (c) Sebastiaan Stok <s.stok@rollerscapes.net>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Rollerworks\Component\Search\Extension\Doctrine\Dbal\Conversion;

use Doctrine\DBAL\Types\Type as DBALType;
use Rollerworks\Component\Search\Doctrine\Dbal\ConversionHints;
use Rollerworks\Component\Search\Doctrine\Dbal\ConversionStrategyInterface;
use Rollerworks\Component\Search\Doctrine\Dbal\SqlFieldConversionInterface;
use Rollerworks\Component\Search\Doctrine\Dbal\ValueConversionInterface;
use Rollerworks\Component\Search\Exception\UnexpectedTypeException;

/**
 * AgeDateConversion.
 *
 * The chosen conversion strategy is done as follow.
 *
 * * 1: When the provided value is an integer, the DB-value is converted to an age.
 * * 2: When the provided value is an DateTime the input-value is converted to an date string.
 * * 3: When the provided value is an DateTime and the mapping-type is not a date
 *      the input-value is converted to an date string and the DB-value is converted to a date.
 *
 * @author Sebastiaan Stok <s.stok@rollerscapes.net>
 */
class AgeDateConversion implements ConversionStrategyInterface, SqlFieldConversionInterface, ValueConversionInterface
{
    /**
     * {@inheritdoc}
     */
    public function getConversionStrategy($value, array $options, ConversionHints $hints)
    {
        if (!$value instanceof \DateTime && !ctype_digit((string) $value)) {
            throw new UnexpectedTypeException($value, '\DateTime object or integer');
        }

        if ($value instanceof \DateTime) {
            return $hints->field->getDbType()->getName() !== 'date' ? 2 : 3;
        }

        return 1;
    }

    /**
     * {@inheritdoc}
     */
    public function convertSqlField($column, array $options, ConversionHints $hints)
    {
        if (3 === $hints->conversionStrategy) {
            return $column;
        }

        if (2 === $hints->conversionStrategy) {
            return "CAST($column AS DATE)";
        }

        $platform = $hints->connection->getDatabasePlatform()->getName();

        $convertMap = array();
        $convertMap['postgresql'] = "to_char(age(%1\$s), 'YYYY'::text)::integer";
        $convertMap['mysql'] = "(DATE_FORMAT(NOW(), '%%Y') - DATE_FORMAT(%1\$s, '%%Y') - (DATE_FORMAT(NOW(), '00-%%m-%%d') < DATE_FORMAT(%1\$s, '00-%%m-%%d')))";
        $convertMap['drizzle'] = $convertMap['mysql'];
        $convertMap['mssql'] = "DATEDIFF(hour, %1\$s, GETDATE())/8766";
        $convertMap['oracle'] = "trunc((months_between(sysdate, (sysdate - %1\$s)))/12)";
        $convertMap['sqlite'] = "search_conversion_age(%1\$s)";
        $convertMap['mock'] = $convertMap['sqlite'];

        if (isset($convertMap[$platform])) {
            return sprintf($convertMap[$platform], $column);
        }

        throw new \RuntimeException(
            sprintf('Unsupported platform "%s" for AgeDateConversion.', $platform)
        );
    }

    /**
     * {@inheritdoc}
     */
    public function requiresBaseConversion($input, array $options, ConversionHints $hints)
    {
        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function convertValue($value, array $options, ConversionHints $hints)
    {
        if (2 === $hints->conversionStrategy || 3 === $hints->conversionStrategy) {
            return DBALType::getType('date')->convertToDatabaseValue(
                $value,
                $hints->connection->getDatabasePlatform()
            );
        }

        return (int) $value;
    }
}
