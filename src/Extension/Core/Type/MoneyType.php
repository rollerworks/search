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
        if (!class_exists(IntlMoneyParser::class)) {
            throw new \RuntimeException('Unable to use MoneyType without the "moneyphp/money" library.');
        }

        $this->valueComparator = new MoneyValueComparator();
    }

    /**
     * {@inheritdoc}
     */
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

    /**
     * {@inheritdoc}
     */
    public function buildView(SearchFieldView $view, FieldConfig $config, array $options): void
    {
        $view->vars['grouping'] = $options['grouping'];
        $view->vars['default_currency'] = $options['default_currency'];
        $view->vars['increase_by'] = $options['increase_by'];
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(
            [
                'grouping' => false,
                'default_currency' => 'EUR',
                'increase_by' => 'cents',
            ]
        );

        $resolver->setAllowedValues('increase_by', ['cents', 'amount']);
    }

    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix(): string
    {
        return 'money';
    }
}
