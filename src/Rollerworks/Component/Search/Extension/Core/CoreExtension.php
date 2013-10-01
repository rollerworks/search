<?php

/*
 * This file is part of the Rollerworks Search Component package.
 *
 * (c) Sebastiaan Stok <s.stok@rollerscapes.net>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Rollerworks\Component\Search\Extension\Core;

use Rollerworks\Component\Search\AbstractExtension;

/**
 * Represents the main search extension, which loads the core functionality.
 *
 * @author Sebastiaan Stok <s.stok@rollerscapes.net>
 */
class CoreExtension extends AbstractExtension
{
    /**
     * {@inheritdoc}
     */
    protected function loadTypes()
    {
        $simpleValueComparison = new ValueComparison\SimpleValueComparison();
        $dateTimeComparison = new ValueComparison\DateTimeValueComparison();
        $numberComparison = new ValueComparison\NumberValueComparison();

        return array(
            new Type\FieldType($simpleValueComparison),
            new Type\DateType(new ValueComparison\DateValueComparison()),
            new Type\DateTimeType($dateTimeComparison),
            new Type\TimeType($dateTimeComparison),
            new Type\BirthdayType(new ValueComparison\BirthdayValueComparison()),
            new Type\ChoiceType($simpleValueComparison),
            new Type\CountryType(),
            new Type\IntegerType($numberComparison),
            new Type\LanguageType(),
            new Type\LocaleType($simpleValueComparison),
            new Type\MoneyType(new ValueComparison\MoneyValueComparison()),
            new Type\NumberType($numberComparison),
            new Type\TextType($simpleValueComparison),

            new Type\TimezoneType($simpleValueComparison),
            new Type\CurrencyType($simpleValueComparison),
        );
    }
}
