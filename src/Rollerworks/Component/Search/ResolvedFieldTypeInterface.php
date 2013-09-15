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

/**
* A wrapper for a field type and its extensions.
*/
interface ResolvedFieldTypeInterface
{
    /**
     * Returns the name of the type.
     *
     * @return string The type name.
     */
    public function getName();

    /**
     * Returns the parent type.
     *
     * @return ResolvedFieldTypeInterface|null The parent type or null.
     */
    public function getParent();

    /**
     * Returns the wrapped form type.
     *
     * @return FieldTypeInterface The wrapped form type.
     */
    public function getInnerType();

    /**
     * Returns the extensions of the wrapped form type.
     *
     * @return FieldTypeExtensionInterface[] An array of {@link FormTypeExtensionInterface} instances.
     */
    public function getTypeExtensions();

    /**
     * This configures the {@link FieldConfigInterface}.
     *
     * This method is called for each type in the hierarchy starting from the
     * top most type. Type extensions can further modify the field.
     *
     * @param FieldConfigInterface $config
     * @param array                $options
     */
    public function buildType(FieldConfigInterface $config, array $options);

    /**
     * Returns the configured options resolver used for this type.
     *
     * @return \Symfony\Component\OptionsResolver\OptionsResolverInterface The options resolver.
     */
    public function getOptionsResolver();
}
