<?php

/**
 * This file is part of the RollerworksSearch Component package.
 *
 * (c) 2012-2014 Sebastiaan Stok <s.stok@rollerscapes.net>
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
     * {@inheritDoc}
     */
    public function buildType(FieldConfigInterface $config, array $options)
    {
    }

    /**
     * {@inheritDoc}
     */
    public function buildFieldView(SearchFieldView $view, FieldConfigInterface $config, array $options)
    {

    }

    /**
     * {@inheritDoc}
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
    }

    /**
     * {@inheritDoc}
     */
    public function hasRangeSupport()
    {
        return false;
    }

    /**
     * {@inheritDoc}
     */
    public function hasCompareSupport()
    {
        return false;
    }

    /**
     * {@inheritDoc}
     */
    public function hasPatternMatchSupport()
    {
        return false;
    }

    /**
     * {@inheritDoc}
     */
    public function getParent()
    {
        return 'field';
    }
}
