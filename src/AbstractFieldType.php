<?php

/*
 * This file is part of the Rollerworks Search Component package.
 *
 * (c) Sebastiaan Stok <s.stok@rollerscapes.net>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
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
