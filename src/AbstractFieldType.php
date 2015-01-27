<?php

/*
 * This file is part of the RollerworksSearch Component package.
 *
 * (c) Sebastiaan Stok <s.stok@rollerscapes.net>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Rollerworks\Component\Search;

use Symfony\Component\OptionsResolver\OptionsResolverInterface;

/**
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
    public function buildFieldView(SearchFieldView $view, FieldConfigInterface $config, array $options)
    {
    }

    /**
     * {@inheritdoc}
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
    }

    /**
     * {@inheritdoc}
     */
    public function hasRangeSupport()
    {
        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function hasCompareSupport()
    {
        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function hasPatternMatchSupport()
    {
        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function getParent()
    {
        return 'field';
    }
}
