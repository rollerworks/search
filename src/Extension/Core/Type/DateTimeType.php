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
use Rollerworks\Component\Search\ValueComparisonInterface;
use Rollerworks\Component\Search\ValuesBag;
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

        if ($options['pattern']) {
            $options['format'] = $options['pattern'];
        }

        if (null === $options['format']) {
            if (!in_array($options['date_format'], self::$acceptedFormats, true)) {
                throw new InvalidConfigurationException(
                    'The "date_format" option must be one of the IntlDateFormatter constants '.
                    '(FULL, LONG, MEDIUM, SHORT) or the "format" must be a string representing a custom format.'
                );
            }

            if (!in_array($options['time_format'], self::$acceptedFormats, true)) {
                throw new InvalidConfigurationException(
                    'The "time_format" option must be one of the IntlDateFormatter constants '.
                    '(FULL, LONG, MEDIUM, SHORT) or the "format" must be a string representing a custom format.'
                );
            }
        }

        if (self::HTML5_FORMAT === $options['format']) {
            $config->addViewTransformer(
                new DateTimeToRfc3339Transformer(
                    $options['model_timezone'],
                    $options['view_timezone']
                )
            );
        } else {
            $config->addViewTransformer(
                new DateTimeToLocalizedStringTransformer(
                    $options['model_timezone'],
                    $options['view_timezone'],
                    $options['date_format'],
                    $options['time_format'],
                    \IntlDateFormatter::GREGORIAN,
                    $options['format']
                )
            );
        }
    }

    /**
     * {@inheritdoc}
     */
    public function buildView(SearchFieldView $view, FieldConfigInterface $config, array $options)
    {
        $format = $options['format'];

        if (null === $format) {
            $format = \IntlDateFormatter::create(
                \Locale::getDefault(),
                $options['date_format'],
                $options['time_format'],
                $options['view_timezone'],
                \IntlDateFormatter::GREGORIAN
            )->getPattern();
        }

        $view->vars['timezone'] = $options['view_timezone'] ?: date_default_timezone_get();
        $view->vars['format'] = $format;
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
                'format' => null,
                'date_format' => self::DEFAULT_DATE_FORMAT,
                'time_format' => self::DEFAULT_TIME_FORMAT,
            ]
        );

        // BC to be removed in 2.0.
        $formatNormalizer = function (Options $options, $value) {
            if (null === $value && null !== $options['pattern']) {
                return $options['pattern'];
            }

            return $value;
        };

        $resolver->setNormalizer('format', $formatNormalizer);
        $resolver->setAllowedTypes('pattern', ['string', 'null']);
        $resolver->setAllowedTypes('format', ['string', 'null']);
        $resolver->setAllowedTypes('date_format', ['int']);
        $resolver->setAllowedTypes('time_format', ['int']);
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'datetime';
    }
}
