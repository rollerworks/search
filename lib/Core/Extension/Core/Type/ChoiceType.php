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

use Rollerworks\Component\Search\Extension\Core\ChoiceList\ChoiceList;
use Rollerworks\Component\Search\Extension\Core\ChoiceList\Factory\CachingFactoryDecorator;
use Rollerworks\Component\Search\Extension\Core\ChoiceList\Factory\ChoiceListFactory;
use Rollerworks\Component\Search\Extension\Core\ChoiceList\Factory\DefaultChoiceListFactory;
use Rollerworks\Component\Search\Extension\Core\ChoiceList\Factory\PropertyAccessDecorator;
use Rollerworks\Component\Search\Extension\Core\ChoiceList\Loader\ChoiceLoader;
use Rollerworks\Component\Search\Extension\Core\ChoiceList\View\ChoiceListView;
use Rollerworks\Component\Search\Extension\Core\DataTransformer\ChoiceToLabelTransformer;
use Rollerworks\Component\Search\Extension\Core\DataTransformer\ChoiceToValueTransformer;
use Rollerworks\Component\Search\Field\AbstractFieldType;
use Rollerworks\Component\Search\Field\FieldConfig;
use Rollerworks\Component\Search\Field\SearchFieldView;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\PropertyAccess\PropertyPath;

/**
 * @author Sebastiaan Stok <s.stok@rollerscapes.net>
 */
final class ChoiceType extends AbstractFieldType
{
    private $choiceListFactory;

    public function __construct(ChoiceListFactory $choiceListFactory = null)
    {
        $this->choiceListFactory = $choiceListFactory ?? new CachingFactoryDecorator(
            new PropertyAccessDecorator(
                new DefaultChoiceListFactory()
            )
        );
    }

    public function buildType(FieldConfig $config, array $options): void
    {
        $choiceList = $this->createChoiceList($options);
        $config->setAttribute('choice_list', $choiceList);

        // Initialize all choices before doing the index check below.
        // This helps in cases where index checks are optimized for non
        // initialized choice lists. For example, when using an SQL driver,
        // the index check would read in one SQL query and the initialization
        // requires another SQL query. When the initialization is done first,
        // one SQL query is sufficient.

        $choiceListView = $this->createChoiceListView($choiceList, $options);
        $config->setAttribute('choice_list_view', $choiceListView);

        // Force label when values are not constant.
        if (! $choiceList->isValuesConstant()) {
            $options['view_format'] = 'label';
            $options['norm_format'] = 'label';
        }

        if ($options['view_format'] === 'label') {
            $config->setViewTransformer(new ChoiceToLabelTransformer($choiceList, $choiceListView));
        } elseif ($options['view_format'] === 'value') {
            $config->setViewTransformer(new ChoiceToValueTransformer($choiceList));
        }

        if ($options['norm_format'] === 'label') {
            $config->setNormTransformer(new ChoiceToLabelTransformer($choiceList, $choiceListView));
        } elseif ($options['norm_format'] === 'value') {
            $config->setNormTransformer(new ChoiceToValueTransformer($choiceList));
        }
    }

    public function buildView(SearchFieldView $view, FieldConfig $config, array $options): void
    {
        /** @var ChoiceListView $choiceListView */
        $choiceListView = $config->getAttribute('choice_list_view');

        $view->vars = array_replace($view->vars, [
            'preferred_choices' => $choiceListView->preferredChoices,
            'choices' => $choiceListView->choices,
            'separator' => '-------------------',
            'choice_translation_domain' => $options['choice_translation_domain'],
        ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $choiceTranslationDomainNormalizer = static function (Options $options, $choiceTranslationDomain) {
            if ($choiceTranslationDomain === true) {
                return $options['translation_domain'];
            }

            return $choiceTranslationDomain;
        };

        $resolver->setDefaults([
            'choices' => [],
            'view_format' => 'label',
            'norm_format' => 'value',
            'choice_loader' => null,
            'choice_label' => null,
            'choice_name' => null,
            'choice_value' => null,
            'choice_attr' => null,
            'preferred_choices' => [],
            'group_by' => null,
            'choice_translation_domain' => true,
        ]);

        $resolver->setNormalizer('choice_translation_domain', $choiceTranslationDomainNormalizer);

        $resolver->setAllowedTypes('choices', ['null', 'array', \Traversable::class]);
        $resolver->setAllowedTypes('choice_translation_domain', ['null', 'bool', 'string']);
        $resolver->setAllowedTypes('choice_loader', ['null', ChoiceLoader::class]);
        $resolver->setAllowedTypes('choice_label', ['null', 'bool', 'callable', 'string', PropertyPath::class]);
        $resolver->setAllowedTypes('choice_name', ['null', 'callable', 'string',  PropertyPath::class]);
        $resolver->setAllowedTypes('choice_value', ['null', 'callable', 'string',  PropertyPath::class]);
        $resolver->setAllowedTypes('choice_attr', ['null', 'array', 'callable', 'string',  PropertyPath::class]);
        $resolver->setAllowedTypes('preferred_choices', ['array', '\Traversable', 'callable', 'string',  PropertyPath::class]);
        $resolver->setAllowedTypes('group_by', ['null', 'callable', 'string',  PropertyPath::class]);

        // Note that 'view_format' and 'norm_format' only affects the transformation.
        // But not the Widget view display (which always uses the label).
        $resolver->setAllowedValues('view_format', ['auto', 'label', 'value']);
        $resolver->setAllowedValues('norm_format', ['auto', 'label', 'value']);
        $resolver->setNormalizer(
            'view_format',
            static fn (Options $options, $value) => $value === 'auto' ? 'label' : $value
        );
        $resolver->setNormalizer(
            'norm_format',
            static fn (Options $options, $value) => $value === 'auto' ? 'value' : $value
        );
    }

    public function getBlockPrefix(): string
    {
        return 'choice';
    }

    private function createChoiceList(array $options): ChoiceList
    {
        if ($options['choice_loader'] !== null) {
            return $this->choiceListFactory->createListFromLoader(
                $options['choice_loader'],
                $options['choice_value']
            );
        }

        // Harden against NULL values
        $choices = $options['choices'] !== null ? $options['choices'] : [];

        return $this->choiceListFactory->createListFromChoices($choices, $options['choice_value']);
    }

    private function createChoiceListView(ChoiceList $choiceList, array $options): ChoiceListView
    {
        return $this->choiceListFactory->createView(
            $choiceList,
            $options['preferred_choices'],
            $options['choice_label'],
            $options['choice_name'],
            $options['group_by'],
            $options['choice_attr']
        );
    }
}
