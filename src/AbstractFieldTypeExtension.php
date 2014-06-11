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
    public function buildFieldView(FieldConfigInterface $config, SearchFieldView $view)
    {
    }

    /**
     * {@inheritdoc}
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
    }
}
