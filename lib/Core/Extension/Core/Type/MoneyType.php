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

namespace Rollerworks\Component\Search\Extension\Core\Type;

use Money\Parser\IntlMoneyParser;
use Rollerworks\Component\Search\Extension\Core\DataTransformer\MoneyToLocalizedStringTransformer;
use Rollerworks\Component\Search\Extension\Core\DataTransformer\MoneyToStringTransformer;
use Rollerworks\Component\Search\Extension\Core\ValueComparator\MoneyValueComparator;
use Rollerworks\Component\Search\Field\AbstractFieldType;
use Rollerworks\Component\Search\Field\FieldConfig;
use Rollerworks\Component\Search\Field\SearchFieldView;
use Rollerworks\Component\Search\Input\NormStringQueryInput;
use Rollerworks\Component\Search\Input\StringLexer;
use Rollerworks\Component\Search\Input\StringQueryInput;
use Rollerworks\Component\Search\Value\Compare;
use Rollerworks\Component\Search\Value\Range;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * @author Sebastiaan Stok <s.stok@rollerscapes.net>
 */
final class MoneyType extends AbstractFieldType
{
    private $valueComparator;

    public function __construct()
    {
        if (! class_exists(IntlMoneyParser::class)) {
            throw new \RuntimeException('Unable to use MoneyType without the "moneyphp/money" library.');
        }

        $this->valueComparator = new MoneyValueComparator();
    }

    public function buildType(FieldConfig $config, array $options): void
    {
        $config->setValueComparator($this->valueComparator);
        $config->setValueTypeSupport(Range::class, true);
        $config->setValueTypeSupport(Compare::class, true);

        $config->setViewTransformer(
            new MoneyToLocalizedStringTransformer(
                $options['default_currency'],
                $options['grouping']
            )
        );

        $config->setNormTransformer(
            new MoneyToStringTransformer($options['default_currency'])
        );
    }

    public function buildView(SearchFieldView $view, FieldConfig $config, array $options): void
    {
        $view->vars['grouping'] = $options['grouping'];
        $view->vars['default_currency'] = $options['default_currency'];
        $view->vars['increase_by'] = $options['increase_by'];
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(
            [
                'grouping' => false,
                'default_currency' => 'EUR',
                'increase_by' => 'cents',
                NormStringQueryInput::FIELD_LEXER_OPTION_NAME => static function (StringLexer $lexer, string $allowedNext): string {
                    if ($lexer->isGlimpse('"')) {
                        return $lexer->stringValue($allowedNext);
                    }

                    return $lexer->expects('/([A-Z]{3} )?(-?(\d+)?(\.\d+)?)/As', 'Money format as CUR xx.xx. Eg. EUR 12.00');
                },
                StringQueryInput::FIELD_LEXER_OPTION_NAME => static function (StringLexer $lexer, string $allowedNext): string {
                    if ($lexer->isGlimpse('"')) {
                        return $lexer->stringValue($allowedNext);
                    }

                    // Currencies can be more complex then you would expect. Some locales even use spaces!
                    // And this needs to support as much combinations as possible.
                    //
                    // The currency is matched before and/or after. Again this is not about validating but matching.

                    return $lexer->expects('/\p{Sc}?(\xc2\xa0|\h)?-?\p{N}+((\xc2\xa0|\h|\.)\p{N}{2,3})*-?((\xc2\xa0|\h)?\p{Sc})?/Aus', 'MoneyFormat');
                },

                StringQueryInput::VALUE_EXPORTER_OPTION_NAME => static function ($value, callable $transformer, FieldConfig $config) {
                    $transformedValue = $transformer($value, $config);

                    if (mb_strpos($transformedValue, ',') !== false) {
                        $transformedValue = '"' . $transformedValue . '"';
                    }

                    return $transformedValue;
                },
                NormStringQueryInput::VALUE_EXPORTER_OPTION_NAME => true,
            ]
        );

        $resolver->setAllowedValues('increase_by', ['cents', 'amount']);
    }

    public function getBlockPrefix(): string
    {
        return 'money';
    }
}
