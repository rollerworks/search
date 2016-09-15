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
use Rollerworks\Component\Search\Extension\Core\DataTransformer\BirthdayTransformer;
use Rollerworks\Component\Search\FieldConfigInterface;
use Rollerworks\Component\Search\SearchFieldView;
use Rollerworks\Component\Search\ValueComparisonInterface;
use Rollerworks\Component\Search\ValuesBag;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * @author Sebastiaan Stok <s.stok@rollerscapes.net>
 */
class BirthdayType extends AbstractFieldType
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
        $config->setValueComparison($this->valueComparison);
        $config->setValueTypeSupport(ValuesBag::VALUE_TYPE_RANGE, true);
        $config->setValueTypeSupport(ValuesBag::VALUE_TYPE_COMPARISON, true);

        $viewTransformers = $config->getViewTransformers();

        $config->resetViewTransformers();
        $config->addViewTransformer(
            new BirthdayTransformer($viewTransformers, $options['allow_age'], $options['allow_future_date'])
        );
    }

    /**
     * {@inheritdoc}
     */
    public function buildView(SearchFieldView $view, FieldConfigInterface $config, array $options)
    {
        $view->vars['allow_age'] = $options['allow_age'];
        $view->vars['allow_future_date'] = $options['allow_future_date'];
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'allow_age' => true,
            'allow_future_date' => false,
            'invalid_message' => function (Options $options) {
                if ($options['allow_age']) {
                    return 'This value is not a valid birthday or age.';
                }

                return 'This value is not a valid birthday.';
            },
        ]);

        $resolver->setAllowedTypes('allow_age', ['bool']);
        $resolver->setAllowedTypes('allow_future_date', ['bool']);
    }

    /**
     * Returns the name of the type.
     *
     * @return string The type name
     */
    public function getName()
    {
        return 'birthday';
    }

    /**
     * Returns the name of the type.
     *
     * @return string The type name
     */
    public function getParent()
    {
        return 'date';
    }
}
