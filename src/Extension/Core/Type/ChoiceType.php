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
use Rollerworks\Component\Search\Extension\Core\ChoiceList\SimpleChoiceList;
use Rollerworks\Component\Search\Extension\Core\DataTransformer\ChoiceToLabelTransformer;
use Rollerworks\Component\Search\Extension\Core\DataTransformer\ChoiceToValueTransformer;
use Rollerworks\Component\Search\FieldConfigInterface;
use Rollerworks\Component\Search\SearchFieldView;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * @author Sebastiaan Stok <s.stok@rollerscapes.net>
 */
class ChoiceType extends AbstractFieldType
{
    /**
     * Caches created choice lists.
     *
     * @var array
     */
    private $choiceListCache = [];

    /**
     * {@inheritdoc}
     */
    public function buildType(FieldConfigInterface $config, array $options)
    {
        if (!$options['choice_list'] && !$options['choices'] instanceof \Traversable && !is_array($options['choices'])) {
            throw new InvalidConfigurationException('Either the option "choices" or "choice_list" must be set.');
        }

        if ($options['label_as_value']) {
            $config->addViewTransformer(
                new ChoiceToLabelTransformer($options['choice_list'])
            );
        } else {
            $config->addViewTransformer(
                new ChoiceToValueTransformer($options['choice_list'])
            );
        }
    }

    /**
     * {@inheritdoc}
     */
    public function buildView(SearchFieldView $view, FieldConfigInterface $config, array $options)
    {
        $view->vars['choices'] = $options['choice_list'];
        $view->vars['label_as_value'] = $options['label_as_value'];
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $choiceListCache = &$this->choiceListCache;

        $choiceList = function (Options $options) use (&$choiceListCache) {
            // Harden against NULL values (like in EntityType and ModelType)
            $choices = null !== $options['choices'] ? $options['choices'] : [];

            // Reuse existing choice lists in order to increase performance
            $hash = hash('sha256', serialize([$choices]));

            if (!isset($choiceListCache[$hash])) {
                $choiceListCache[$hash] = new SimpleChoiceList($choices);
            }

            return $choiceListCache[$hash];
        };

        $resolver->setDefaults([
            'label_as_value' => false,
            'choice_list' => $choiceList,
            'choices' => [],
        ]);

        $resolver->setAllowedTypes(
            'choice_list',
            [
                'null',
                'Rollerworks\Component\Search\Extension\Core\ChoiceList\ChoiceListInterface',
            ]
        );
    }

    /**
     * Returns the name of the type.
     *
     * @return string The type name
     */
    public function getName()
    {
        return 'choice';
    }
}
