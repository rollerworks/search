<?php

/*
 * This file is part of the RollerworksSearch package.
 *
 * (c) Sebastiaan Stok <s.stok@rollerscapes.net>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
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
        $dateTimeComparison = new ValueComparison\DateTimeValueComparison();
        $numberComparison = new ValueComparison\NumberValueComparison();

        return [
            new Type\FieldType(new ValueComparison\SimpleValueComparison()),
            new Type\DateType(new ValueComparison\DateValueComparison()),
            new Type\DateTimeType($dateTimeComparison),
            new Type\TimeType($dateTimeComparison),
            new Type\BirthdayType(new ValueComparison\BirthdayValueComparison()),
            new Type\ChoiceType(),
            new Type\CountryType(),
            new Type\IntegerType($numberComparison),
            new Type\LanguageType(),
            new Type\LocaleType(),
            new Type\MoneyType(new ValueComparison\MoneyValueComparison()),
            new Type\NumberType($numberComparison),
            new Type\TextType(),
            new Type\TimezoneType(),
            new Type\CurrencyType(),
        ];
    }
}
