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
use Rollerworks\Component\Search\Extension\Core\DataTransformer\DateTimeToStringTransformer;
use Rollerworks\Component\Search\FieldConfigInterface;
use Rollerworks\Component\Search\ValueComparisonInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

/**
 * @author Sebastiaan Stok <s.stok@rollerscapes.net>
 */
class TimeType extends AbstractFieldType
{
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
     * {@inheritDoc}
     */
    public function buildType(FieldConfigInterface $config, array $options)
    {
        $config->setValueComparison($this->valueComparison);

        $format = 'H';

        if ($options['with_seconds'] && !$options['with_minutes']) {
            throw new InvalidConfigurationException('You can not disable minutes if you have enabled seconds.');
        }

        if ($options['with_minutes']) {
            $format .= ':i';
            $parts[] = 'minute';
        }

        if ($options['with_seconds']) {
            $format .= ':s';
            $parts[] = 'second';
        }

        $config->addViewTransformer(new DateTimeToStringTransformer($options['model_timezone'], $options['view_timezone'], $format));
    }

    /**
     * {@inheritDoc}
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(array(
            'with_minutes'   => true,
            'with_seconds'   => false,
            'view_timezone' => null,
            'model_timezone' => null,
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
        return 'time';
    }
}
