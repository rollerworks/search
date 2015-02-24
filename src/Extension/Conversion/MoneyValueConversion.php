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

use Doctrine\DBAL\Types\Type as DbType;
use Rollerworks\Component\Search\Doctrine\Dbal\ConversionHints;
use Rollerworks\Component\Search\Doctrine\Dbal\SqlFieldConversionInterface;
use Rollerworks\Component\Search\Doctrine\Dbal\SqlValueConversionInterface;
use Rollerworks\Component\Search\Exception\UnexpectedTypeException;
use Rollerworks\Component\Search\Extension\Core\Model\MoneyValue;

class MoneyValueConversion implements SqlValueConversionInterface, SqlFieldConversionInterface
{
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
    public function convertSqlValue($value, array $options, ConversionHints $hints)
    {
        $value = $hints->connection->quote($value);
        $castType = $this->getCastType($options['precision'], $hints);

        // https://github.com/rollerworks/rollerworks-search-doctrine-dbal/issues/9
        return "CAST({$value} AS {$castType})";
    }

    /**
     * {@inheritdoc}
     */
    public function convertSqlField($column, array $options, ConversionHints $hints)
    {
        if (DbType::DECIMAL === $hints->field->getDbType()->getName()) {
            return $column;
        }

        $substr = $hints->connection->getDatabasePlatform()->getSubstringExpression($column, 5);
        $castType = $this->getCastType($options['precision'], $hints);

        return "CAST($substr AS $castType)";
    }

    /**
     * {@inheritdoc}
     */
    public function convertValue($input, array $options, ConversionHints $hints)
    {
        if (!$input instanceof MoneyValue) {
            throw new UnexpectedTypeException($input, 'Rollerworks\Component\Search\Extension\Core\Model\MoneyValue');
        }

        return $input->value;
    }

    private function getCastType($scale, ConversionHints $hints)
    {
        if (false !== strpos($hints->connection->getDatabasePlatform()->getName(), 'mysql')) {
            return "DECIMAL(10, {$scale})";
        }

        return $hints->connection->getDatabasePlatform()->getDecimalTypeDeclarationSQL(
            array('scale' => $scale)
        );
    }
}
