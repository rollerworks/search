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

namespace Rollerworks\Component\Search\Field;

use Rollerworks\Component\Search\Extension\Core\DataTransformer\OrderTransformer;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * @author Dalibor Karlović <dalibor@flexolabs.io>
 */
final class OrderFieldType implements FieldType
{
    public function getParent(): ?string
    {
        return null;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'default' => null,
            'case' => OrderTransformer::CASE_UPPERCASE,
            'alias' => ['ASC' => 'ASC', 'DESC' => 'DESC'],
            'type' => null,
            'type_options' => [],
        ]);

        $resolver->setAllowedValues('case', [
            OrderTransformer::CASE_LOWERCASE,
            OrderTransformer::CASE_UPPERCASE,
        ]);
        $resolver->setAllowedTypes('alias', 'array');
        $resolver->setAllowedTypes('default', ['null', 'string']);
        $resolver->setAllowedTypes('type', ['string', 'null']);
        $resolver->setAllowedTypes('type_options', ['array']);
    }

    public function buildType(FieldConfig $config, array $options): void
    {
        $transformer = new OrderTransformer($options['alias'], $options['case'], $options['default']);

        $config->setNormTransformer($transformer);
        $config->setViewTransformer($transformer);
    }

    public function buildView(SearchFieldView $view, FieldConfig $config, array $options): void
    {
    }

    public function getBlockPrefix(): string
    {
        return 'order';
    }
}
