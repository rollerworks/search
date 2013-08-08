<?php

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
     * Returns the configured options resolver used for this type.
     *
     * @return \Symfony\Component\OptionsResolver\OptionsResolverInterface The options resolver.
     */
    public function getOptionsResolver();
}
