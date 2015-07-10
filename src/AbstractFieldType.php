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
 * The AbstractFieldType can be used as a base class implementation for FieldTypes.
 *
 * An added bonus for extending this class rather then the implementing the the
 * {@link FieldTypeInterface} is that any new methods added the
 * FieldTypeInterface will not break existing implementations.
 *
 * @author Sebastiaan Stok <s.stok@rollerscapes.net>
 */
abstract class AbstractFieldType implements FieldTypeInterface
{
    /**
     * {@inheritdoc}
     */
    public function buildType(FieldConfigInterface $config, array $options)
    {
    }

    /**
     * {@inheritdoc}
     */
    public function buildView(SearchFieldView $view, FieldConfigInterface $config, array $options)
    {
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
    }

    /**
     * {@inheritdoc}
     */
    public function getParent()
    {
        return 'field';
    }
}
