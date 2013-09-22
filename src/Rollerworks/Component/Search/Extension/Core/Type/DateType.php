<?php

/*
 * This file is part of the Rollerworks Search Component package.
 *
 * (c) Sebastiaan Stok <s.stok@rollerscapes.net>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Rollerworks\Component\Search\Extension\Core\Type;

use Rollerworks\Component\Search\AbstractFieldType;
use Rollerworks\Component\Search\Exception\InvalidConfigurationException;
use Rollerworks\Component\Search\Extension\Core\DataTransformer\DateTimeToLocalizedStringTransformer;
use Rollerworks\Component\Search\FieldConfigInterface;
use Rollerworks\Component\Search\ValueComparisonInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

class DateType extends AbstractFieldType
{
    const DEFAULT_FORMAT = \IntlDateFormatter::MEDIUM;

    const HTML5_FORMAT = 'yyyy-MM-dd';

    private static $acceptedFormats = array(
        \IntlDateFormatter::FULL,
        \IntlDateFormatter::LONG,
        \IntlDateFormatter::MEDIUM,
        \IntlDateFormatter::SHORT,
    );

    /**
     * @var ValueComparisonInterface
     */
    protected $valueComparison;

    public function __construct(ValueComparisonInterface $valueComparison)
    {
        $this->valueComparison = $valueComparison;
    }

    /**
     * {@inheritDoc}
     */
    public function buildType(FieldConfigInterface $config, array $options)
    {
        $config->setValueComparison($this->valueComparison);

        $dateFormat = is_int($options['format']) ? $options['format'] : self::DEFAULT_FORMAT;
        $timeFormat = \IntlDateFormatter::NONE;
        $calendar = \IntlDateFormatter::GREGORIAN;
        $pattern = is_string($options['format']) ? $options['format'] : null;

        if (!in_array($dateFormat, self::$acceptedFormats, true)) {
            throw new InvalidConfigurationException('The "format" option must be one of the IntlDateFormatter constants (FULL, LONG, MEDIUM, SHORT) or a string representing a custom format.');
        }

        if (null !== $pattern && (false === strpos($pattern, 'y') || false === strpos($pattern, 'M') || false === strpos($pattern, 'd'))) {
            throw new InvalidConfigurationException(sprintf('The "format" option should contain the letters "y", "M" and "d". Its current value is "%s".', $pattern));
        }

        $config->addViewTransformer(new DateTimeToLocalizedStringTransformer(
            $options['model_timezone'],
            $options['view_timezone'],
            $dateFormat,
            $timeFormat,
            $calendar,
            $pattern
        ));
    }

    /**
     * {@inheritDoc}
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(array(
            'format' => DateType::DEFAULT_FORMAT,
            'model_timezone' => null,
            'input_timezone' => null,
        ));

        $resolver->setAllowedTypes(array(
            'format' => array('int', 'string'),
        ));
    }

    /**
     * {@inheritDoc}
     */
    public function hasRangeSupport()
    {
        return true;
    }

    /**
     * {@inheritDoc}
     */
    public function hasCompareSupport()
    {
        return true;
    }

    /**
     * Returns the name of the type.
     *
     * @return string The type name.
     */
    public function getName()
    {
        return 'date';
    }
}
