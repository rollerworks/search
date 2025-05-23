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

use Doctrine\DBAL\Types\Types as DbType;
use Money\Currencies\ISOCurrencies;
use Money\Formatter\DecimalMoneyFormatter;
use Rollerworks\Component\Search\Doctrine\Dbal\ColumnConversion;
use Rollerworks\Component\Search\Doctrine\Dbal\ConversionHints;
use Rollerworks\Component\Search\Doctrine\Dbal\ValueConversion;
use Rollerworks\Component\Search\Extension\Core\Model\MoneyValue;

final class MoneyValueConversion implements ValueConversion, ColumnConversion
{
    /** @var DecimalMoneyFormatter */
    private $formatter;

    /** @var ISOCurrencies */
    private $currencies;

    public function __construct()
    {
        $this->currencies = new ISOCurrencies();
        $this->formatter = new DecimalMoneyFormatter($this->currencies);
    }

    /**
     * @param MoneyValue $value
     */
    public function convertValue($value, array $options, ConversionHints $hints): string
    {
        $sqlValue = $hints->createParamReferenceFor($this->formatter->format($value->value));
        $castType = $this->getCastType($this->currencies->subunitFor($value->value->getCurrency()), $hints);

        return "CAST({$sqlValue} AS {$castType})";
    }

    public function convertColumn(string $column, array $options, ConversionHints $hints): string
    {
        if ($hints->field->dbTypeName === DbType::DECIMAL) {
            return $column;
        }

        $processingValue = $hints->getProcessingValue();
        \assert($processingValue instanceof MoneyValue);

        $substr = $hints->connection->getDatabasePlatform()->getSubstringExpression($column, '5');
        $castType = $this->getCastType($this->currencies->subunitFor($processingValue->value->getCurrency()), $hints);

        return "CAST({$substr} AS {$castType})";
    }

    private function getCastType(int $scale, ConversionHints $hints): string
    {
        if ($hints->getPlatformName() === 'mysql') {
            return "DECIMAL(10, {$scale})";
        }

        return $hints->connection->getDatabasePlatform()->getDecimalTypeDeclarationSQL(
            ['scale' => $scale, 'precision' => 10, 'name' => $hints->field->mappingName]
        );
    }
}
