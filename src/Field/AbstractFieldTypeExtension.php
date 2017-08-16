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

use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * The AbstractFieldTypeExtension can be used as a base implementation
 * for FieldTypeExtensions.
 *
 * An added bonus for extending this class rather then the implementing the the
 * {@link FieldTypeExtensionInterface} is that any new methods added the
 * FieldTypeExtensionInterface will not break existing implementations.
 */
abstract class AbstractFieldTypeExtension implements FieldTypeExtension
{
    /**
     * {@inheritdoc}
     */
    public function buildType(FieldConfig $builder, array $options): void
    {
    }

    /**
     * {@inheritdoc}
     */
    public function buildView(FieldConfig $config, SearchFieldView $view): void
    {
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
    }
}
