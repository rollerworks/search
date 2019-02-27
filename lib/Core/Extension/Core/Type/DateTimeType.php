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

use Rollerworks\Component\Search\Extension\Core\DataTransformer\DateTimeToHtml5LocalDateTimeTransformer;
use Rollerworks\Component\Search\Extension\Core\DataTransformer\DateTimeToLocalizedStringTransformer;
use Rollerworks\Component\Search\Extension\Core\DataTransformer\DateTimeToRfc3339Transformer;
use Rollerworks\Component\Search\Extension\Core\ValueComparator\DateTimeValueValueComparator;
use Rollerworks\Component\Search\Field\FieldConfig;
use Rollerworks\Component\Search\Field\SearchFieldView;
use Rollerworks\Component\Search\Value\Compare;
use Rollerworks\Component\Search\Value\Range;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * @author Sebastiaan Stok <s.stok@rollerscapes.net>
 * @author Bernhard Schussek <bschussek@gmail.com>
 */
final class DateTimeType extends BaseDateTimeType
{
    public const DEFAULT_DATE_FORMAT = \IntlDateFormatter::MEDIUM;
    public const DEFAULT_TIME_FORMAT = \IntlDateFormatter::MEDIUM;

    private $valueComparator;

    public function __construct()
    {
        $this->valueComparator = new DateTimeValueValueComparator();
    }

    public function buildType(FieldConfig $config, array $options): void
    {
        $config->setValueComparator($this->valueComparator);
        $config->setValueTypeSupport(Range::class, true);
        $config->setValueTypeSupport(Compare::class, true);

        if (null === $options['pattern']) {
            $this->validateFormat('date_format', $options['date_format']);
            $this->validateFormat('time_format', $options['time_format']);
        }

        $config->setViewTransformer(
            new DateTimeToLocalizedStringTransformer(
                $options['model_timezone'],
                $options['view_timezone'],
                $options['date_format'],
                $options['time_format'],
                \IntlDateFormatter::GREGORIAN,
                $options['pattern']
            )
        );

        if ($options['html5']) {
            $config->setNormTransformer(
                new DateTimeToHtml5LocalDateTimeTransformer(
                    $options['model_timezone'],
                    $options['view_timezone']
                )
            );
        } else {
            $config->setNormTransformer(
                new DateTimeToRfc3339Transformer(
                    $options['model_timezone'],
                    $options['view_timezone']
                )
            );
        }
    }

    public function buildView(SearchFieldView $view, FieldConfig $config, array $options): void
    {
        $pattern = $options['pattern'];

        if (null === $pattern) {
            $pattern = \IntlDateFormatter::create(
                \Locale::getDefault(),
                $options['date_format'],
                $options['time_format'],
                $options['view_timezone'],
                \IntlDateFormatter::GREGORIAN
            )->getPattern();
        }

        $view->vars['timezone'] = $options['view_timezone'] ?: date_default_timezone_get();
        $view->vars['pattern'] = $pattern;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(
            [
                'model_timezone' => 'UTC',
                'view_timezone' => null,
                'pattern' => null,
                'date_format' => self::DEFAULT_DATE_FORMAT,
                'time_format' => self::DEFAULT_TIME_FORMAT,
                'html5' => false,
            ]
        );

        $resolver->setAllowedTypes('pattern', ['string', 'null']);
        $resolver->setAllowedTypes('date_format', ['int']);
        $resolver->setAllowedTypes('time_format', ['int']);
        $resolver->setAllowedTypes('html5', 'bool');
    }

    public function getBlockPrefix(): string
    {
        return 'datetime';
    }
}
