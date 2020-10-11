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

use Rollerworks\Component\Search\Exception\InvalidConfigurationException;
use Rollerworks\Component\Search\Extension\Core\DataTransformer\DateTimeToStringTransformer;
use Rollerworks\Component\Search\Extension\Core\ValueComparator\DateTimeValueComparator;
use Rollerworks\Component\Search\Field\AbstractFieldType;
use Rollerworks\Component\Search\Field\FieldConfig;
use Rollerworks\Component\Search\Field\SearchFieldView;
use Rollerworks\Component\Search\Value\Compare;
use Rollerworks\Component\Search\Value\Range;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * @author Sebastiaan Stok <s.stok@rollerscapes.net>
 */
final class TimeType extends AbstractFieldType
{
    private $valueComparator;

    public function __construct()
    {
        $this->valueComparator = new DateTimeValueComparator();
    }

    public function buildType(FieldConfig $config, array $options): void
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

        $config->setValueTypeSupport(Range::class, true);
        $config->setValueTypeSupport(Compare::class, true);
        $config->setValueComparator($this->valueComparator);

        $config->setViewTransformer(
            new DateTimeToStringTransformer(
                'UTC',
                'UTC',
                $format
            )
        );

        $config->setNormTransformer(
            new DateTimeToStringTransformer(
                'UTC',
                'UTC',
                $format
            )
        );
    }

    public function buildView(SearchFieldView $view, FieldConfig $config, array $options): void
    {
        $pattern = 'H';

        if ($options['with_minutes']) {
            $pattern .= ':i';
        }

        if ($options['with_seconds']) {
            $pattern .= ':s';
        }

        $view->vars['pattern'] = $pattern;
        $view->vars['with_minutes'] = $options['with_minutes'];
        $view->vars['with_seconds'] = $options['with_seconds'];
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(
            [
                'with_minutes' => true,
                'with_seconds' => false,
            ]
        );
    }

    public function getBlockPrefix(): string
    {
        return 'time';
    }
}
