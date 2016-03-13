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
use Rollerworks\Component\Search\FieldConfigInterface;
use Rollerworks\Component\Search\SearchFieldView;
use Rollerworks\Component\Search\ValueComparisonInterface;
use Rollerworks\Component\Search\ValuesBag;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * @author Sebastiaan Stok <s.stok@rollerscapes.net>
 * @author Bernhard Schussek <bschussek@gmail.com>
 */
class DateType extends AbstractFieldType
{
    const DEFAULT_FORMAT = \IntlDateFormatter::MEDIUM;

    const HTML5_FORMAT = 'yyyy-MM-dd';

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
     * @var ValueComparisonInterface
     */
    protected $valueComparison;

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

        $dateFormat = is_int($options['format']) ? $options['format'] : self::DEFAULT_FORMAT;
        $timeFormat = \IntlDateFormatter::NONE;
        $calendar = \IntlDateFormatter::GREGORIAN;
        $format = is_string($options['format']) ? $options['format'] : null;

        if (!in_array($dateFormat, self::$acceptedFormats, true)) {
            throw new InvalidConfigurationException(
                'The "format" option must be one of the IntlDateFormatter constants (FULL, LONG, MEDIUM, SHORT) '.
                'or a string representing a custom format.'
            );
        }

        if (null !== $format && (false === strpos($format, 'y') || false === strpos($format, 'M')
            || false === strpos($format, 'd'))
        ) {
            throw new InvalidConfigurationException(
                sprintf(
                    'The "format" option should contain the letters "y", "M" and "d". Its current value is "%s".',
                    $format
                )
            );
        }

        $config->addViewTransformer(
            new DateTimeToLocalizedStringTransformer(
                'UTC',
                'UTC',
                $dateFormat,
                $timeFormat,
                $calendar,
                $format
            )
        );
    }

    /**
     * {@inheritdoc}
     */
    public function buildView(SearchFieldView $view, FieldConfigInterface $config, array $options)
    {
        if (is_string($options['format'])) {
            $format = $options['format'];
        } else {
            $format = \IntlDateFormatter::create(
                \Locale::getDefault(),
                is_int($options['format']) ? $options['format'] : self::DEFAULT_FORMAT,
                \IntlDateFormatter::NONE,
                null,
                \IntlDateFormatter::GREGORIAN
            )->getPattern();
        }

        $view->vars['format'] = $format;
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(
            [
                'format' => self::DEFAULT_FORMAT,
            ]
        );

        $resolver->setAllowedTypes('format', ['int', 'string']);
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'date';
    }
}
