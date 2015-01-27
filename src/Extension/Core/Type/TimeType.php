<?php

/*
 * This file is part of the RollerworksSearch Component package.
 *
 * (c) Sebastiaan Stok <s.stok@rollerscapes.net>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
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
    private $valueComparison;

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
        $format = 'H';

        if ($options['with_seconds'] && !$options['with_minutes']) {
            throw new InvalidConfigurationException('You can not disable minutes if you have enabled seconds.');
        }

        if ($options['with_minutes']) {
            $format .= ':i';
        }

        if ($options['with_seconds']) {
            $format .= ':s';
        }

        $config->setValueComparison($this->valueComparison);
        $config->addViewTransformer(
            new DateTimeToStringTransformer(
                'UTC',
                'UTC',
                $format
            )
        );
    }

    /**
     * {@inheritdoc}
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(
            array(
                'with_minutes' => true,
                'with_seconds' => false,
            )
        );
    }

    /**
     * {@inheritdoc}
     */
    public function hasRangeSupport()
    {
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function hasCompareSupport()
    {
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'time';
    }
}
