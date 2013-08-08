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

interface FieldTypeExtensionInterface
{
    /**
     * Builds the type.
     *
     * This method is called after the extended type has built the type to
     * further modify it.
     *
     * @see FieldTypeInterface::buildType()
     *
     * @param FieldConfigInterface $builder The config builder
     * @param array                $options The options
     */
    public function buildType(FieldConfigInterface $builder, array $options);

    /**
     * Overrides the default options from the extended type.
     *
     * @param OptionsResolverInterface $resolver The resolver for the options.
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver);

    /**
     * Returns the name of the type being extended.
     *
     * @return string The name of the type being extended
     */
    public function getExtendedType();
}
