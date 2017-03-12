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

use Rollerworks\Component\Search\Extension\Core\DataTransformer\IntegerToLocalizedStringTransformer;
use Rollerworks\Component\Search\Extension\Core\DataTransformer\IntegerToStringTransformer;
use Rollerworks\Component\Search\Field\AbstractFieldType;
use Rollerworks\Component\Search\Field\FieldConfig;
use Rollerworks\Component\Search\Field\SearchFieldView;
use Rollerworks\Component\Search\Value\Compare;
use Rollerworks\Component\Search\Value\Range;
use Rollerworks\Component\Search\ValueComparator;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * @author Sebastiaan Stok <s.stok@rollerscapes.net>
 */
class IntegerType extends AbstractFieldType
{
    /**
     * @var ValueComparator
     */
    protected $valueComparator;

    /**
     * Constructor.
     *
     * @param ValueComparator $valueComparator
     */
    public function __construct(ValueComparator $valueComparator)
    {
        $this->valueComparator = $valueComparator;
    }

    /**
     * {@inheritdoc}
     */
    public function buildType(FieldConfig $config, array $options)
    {
        $config->setValueComparator($this->valueComparator);
        $config->setValueTypeSupport(Range::class, true);
        $config->setValueTypeSupport(Compare::class, true);

        $config->setNormTransformer(new IntegerToStringTransformer($options['rounding_mode']));
        $config->setViewTransformer(
            new IntegerToLocalizedStringTransformer($options['grouping'], $options['rounding_mode'])
        );
    }

    /**
     * {@inheritdoc}
     */
    public function buildView(SearchFieldView $view, FieldConfig $config, array $options)
    {
        $view->vars['grouping'] = $options['grouping'];
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(
            [
                'grouping' => false,
                // Integer cast rounds towards 0, so do the same when displaying fractions
                'rounding_mode' => \NumberFormatter::ROUND_DOWN,
            ]
        );

        $resolver->setAllowedValues(
            'rounding_mode',
            [
                \NumberFormatter::ROUND_FLOOR,
                \NumberFormatter::ROUND_DOWN,
                \NumberFormatter::ROUND_HALFDOWN,
                \NumberFormatter::ROUND_HALFEVEN,
                \NumberFormatter::ROUND_HALFUP,
                \NumberFormatter::ROUND_UP,
                \NumberFormatter::ROUND_CEILING,
            ]
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix(): string
    {
        return 'integer';
    }
}
