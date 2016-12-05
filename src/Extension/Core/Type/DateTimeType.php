<?php

/*
 * This file is part of the RollerworksSearch package.
 *
 * (c) Sebastiaan Stok <s.stok@rollerscapes.net>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Rollerworks\Component\Search\Extension\Core\Type;

use Rollerworks\Component\Search\AbstractFieldType;
use Rollerworks\Component\Search\Exception\InvalidConfigurationException;
use Rollerworks\Component\Search\Extension\Core\DataTransformer\DateTimeToLocalizedStringTransformer;
use Rollerworks\Component\Search\Extension\Core\DataTransformer\DateTimeToRfc3339Transformer;
use Rollerworks\Component\Search\FieldConfigInterface;
use Rollerworks\Component\Search\SearchFieldView;
use Rollerworks\Component\Search\Value\ValuesBag;
use Rollerworks\Component\Search\ValueComparisonInterface;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * @author Sebastiaan Stok <s.stok@rollerscapes.net>
 * @author Bernhard Schussek <bschussek@gmail.com>
 */
class DateTimeType extends AbstractFieldType
{
    const DEFAULT_DATE_FORMAT = \IntlDateFormatter::MEDIUM;
    const DEFAULT_TIME_FORMAT = \IntlDateFormatter::MEDIUM;

    /**
     * This is not quite the HTML5 format yet, because ICU lacks the
     * capability of parsing and generating RFC 3339 dates, which
     * are like the below pattern but with a timezone suffix. The
     * timezone suffix is.
     *
     *  * "Z" for UTC
     *  * "(-|+)HH:mm" for other timezones (note the colon!)
     *
     * For more information see:
     *
     * http://userguide.icu-project.org/formatparse/datetime#TOC-Date-Time-Format-Syntax
     * http://www.w3.org/TR/html-markup/input.datetime.html
     * http://tools.ietf.org/html/rfc3339
     *
     * An ICU ticket was created:
     * http://icu-project.org/trac/ticket/9421
     *
     * It was supposedly fixed, but is not available in all PHP installations
     * yet. To temporarily circumvent this issue, DateTimeToRfc3339Transformer
     * is used when the format matches this constant.
     */
    const HTML5_FORMAT = "yyyy-MM-dd'T'HH:mm:ssZZZZZ";

    /**
     * @var ValueComparisonInterface
     */
    private $valueComparison;

    /**
     * @var array
     */
    private static $acceptedFormats = [
        \IntlDateFormatter::FULL,
        \IntlDateFormatter::LONG,
        \IntlDateFormatter::MEDIUM,
        \IntlDateFormatter::SHORT,
    ];

    /**
     * Constructor.
     *
     * @param ValueComparisonInterface $valueComparison
     */
    public function __construct(ValueComparisonInterface $valueComparison)
    {
        $this->valueComparison = $valueComparison;
    }

    /**
     * {@inheritdoc}
     */
    public function buildType(FieldConfigInterface $config, array $options)
    {
        $config->setValueComparison($this->valueComparison);
        $config->setValueTypeSupport(ValuesBag::VALUE_TYPE_RANGE, true);
        $config->setValueTypeSupport(ValuesBag::VALUE_TYPE_COMPARISON, true);

        if (null === $options['pattern']) {
            $this->validateFormat('date_format', $options['date_format']);
            $this->validateFormat('time_format', $options['time_format']);
        }

        if (self::HTML5_FORMAT === $options['pattern']) {
            $config->setViewTransformer(
                new DateTimeToRfc3339Transformer(
                    $options['model_timezone'],
                    $options['view_timezone']
                )
            );
        } else {
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
        }

        $config->setNormTransformer(
            new DateTimeToRfc3339Transformer(
                $options['model_timezone'],
                $options['view_timezone']
            )
        );
    }

    /**
     * {@inheritdoc}
     */
    public function buildView(SearchFieldView $view, FieldConfigInterface $config, array $options)
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

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(
            [
                'model_timezone' => 'UTC',
                'view_timezone' => null,
                'pattern' => null,
                'date_format' => self::DEFAULT_DATE_FORMAT,
                'time_format' => self::DEFAULT_TIME_FORMAT,
            ]
        );

        $resolver->setAllowedTypes('pattern', ['string', 'null']);
        $resolver->setAllowedTypes('date_format', ['int']);
        $resolver->setAllowedTypes('time_format', ['int']);
    }

    private function validateFormat(string $name, $value)
    {
        if (!in_array($value, self::$acceptedFormats, true)) {
            throw new InvalidConfigurationException(
                'The "'.$name.'" option must be one of the IntlDateFormatter constants '.
                '(FULL, LONG, MEDIUM, SHORT) or the "pattern" option must be a string representing a custom format.'
            );
        }
    }
}
