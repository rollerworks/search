<?php

/*
 * This file is part of the RollerworksSearch package.
 *
 * (c) Sebastiaan Stok <s.stok@rollerscapes.net>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Rollerworks\Component\Search;

use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * The AbstractFieldTypeExtension can be used as a base implementation
 * for FieldTypeExtensions.
 *
 * An added bonus for extending this class rather then the implementing the the
 * {@link FieldTypeExtensionInterface} is that any new methods added the
 * FieldTypeExtensionInterface will not break existing implementations.
 */
abstract class AbstractFieldTypeExtension implements FieldTypeExtensionInterface
{
    /**
     * {@inheritdoc}
     */
    public function buildType(FieldConfigInterface $builder, array $options)
    {
    }

    /**
     * {@inheritdoc}
     */
    public function buildView(FieldConfigInterface $config, SearchFieldView $view)
    {
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
    }
}
