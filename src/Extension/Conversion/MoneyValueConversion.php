<?php

declare(strict_types=1);

/*
 * This file is part of the RollerworksSearch package.
 *
 * (c) Sebastiaan Stok <s.stok@rollerscapes.net>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Rollerworks\Component\Search\Extension\Doctrine\Dbal\Conversion;

use Doctrine\DBAL\Types\Type as DbType;
use Money\Currencies\ISOCurrencies;
use Money\Formatter\DecimalMoneyFormatter;
use Rollerworks\Component\Search\Doctrine\Dbal\ColumnConversion;
use Rollerworks\Component\Search\Doctrine\Dbal\ConversionHints;
use Rollerworks\Component\Search\Doctrine\Dbal\StrategySupportedConversion;
use Rollerworks\Component\Search\Doctrine\Dbal\ValueConversion;
use Rollerworks\Component\Search\Exception\UnexpectedTypeException;
use Rollerworks\Component\Search\Extension\Core\Model\MoneyValue;

class MoneyValueConversion implements ValueConversion, ColumnConversion, StrategySupportedConversion
{
    private $formatter;
    private $currencies;

    public function __construct()
    {
        $this->currencies = new ISOCurrencies();
        $this->formatter = new DecimalMoneyFormatter($this->currencies);
    }

    /**
     * {@inheritdoc}
     */
    public function convertValue($value, array $options, ConversionHints $hints)
    {
        if (!$value instanceof MoneyValue) {
            throw new UnexpectedTypeException($value, MoneyValue::class);
        }

        $sqlValue = $hints->connection->quote($this->formatter->format($value->value));
        $castType = $this->getCastType($hints->conversionStrategy, $hints);

        // https://github.com/rollerworks/rollerworks-search-doctrine-dbal/issues/9
        return "CAST({$sqlValue} AS {$castType})";
    }

    /**
     * {@inheritdoc}
     */
    public function convertColumn(string $column, array $options, ConversionHints $hints): string
    {
        if (DbType::DECIMAL === $hints->field->dbType->getName()) {
            return $column;
        }

        $substr = $hints->connection->getDatabasePlatform()->getSubstringExpression($column, 5);
        $castType = $this->getCastType($hints->conversionStrategy, $hints);

        return "CAST($substr AS $castType)";
    }

    /**
     * {@inheritdoc}
     */
    public function getConversionStrategy($value, array $options, ConversionHints $hints): int
    {
        if (!$value instanceof MoneyValue) {
            throw new UnexpectedTypeException($value, MoneyValue::class);
        }

        return $this->currencies->subunitFor($value->value->getCurrency());
    }

    private function getCastType(int $scale, ConversionHints $hints): string
    {
        if (false !== strpos($hints->connection->getDatabasePlatform()->getName(), 'mysql')) {
            return "DECIMAL(10, {$scale})";
        }

        return $hints->connection->getDatabasePlatform()->getDecimalTypeDeclarationSQL(
            ['scale' => $scale]
        );
    }
}
