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

namespace Rollerworks\Component\Search\Extension\Doctrine\Orm\Conversion;

use Doctrine\DBAL\Types\Types as DbType;
use Money\Currencies\ISOCurrencies;
use Money\Formatter\DecimalMoneyFormatter;
use Rollerworks\Component\Search\Doctrine\Dbal\ConversionHints;
use Rollerworks\Component\Search\Doctrine\Orm\ColumnConversion;
use Rollerworks\Component\Search\Doctrine\Orm\ValueConversion;
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
        $scale = $this->currencies->subunitFor($value->value->getCurrency());

        return "SEARCH_MONEY_AS_NUMERIC({$sqlValue}, $scale)";
    }

    public function convertColumn(string $column, array $options, ConversionHints $hints): string
    {
        if ($hints->field->dbType->getName() === DbType::DECIMAL) {
            return $column;
        }

        $scale = $this->currencies->subunitFor($hints->getProcessingValue()->getCurrency());

        return "SUBSTRING(SEARCH_MONEY_AS_NUMERIC($column, $scale), 5))";
    }
}
