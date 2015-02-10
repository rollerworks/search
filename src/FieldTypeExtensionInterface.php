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

use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * @author Sebastiaan Stok <s.stok@rollerscapes.net>
 */
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
     *
     * @return void
     */
    public function buildType(FieldConfigInterface $builder, array $options);

    /**
     * Builds the SearchFieldView.
     *
     * @param FieldConfigInterface $config
     * @param SearchFieldView      $view
     *
     * @return void
     */
    public function buildFieldView(FieldConfigInterface $config, SearchFieldView $view);

    /**
     * Overrides the default options from the extended type.
     *
     * @param OptionsResolver|OptionsResolverInterface $resolver The resolver for the options.
     *
     * @return
     */
    public function configureOptions(OptionsResolver $resolver);

    /**
     * Returns the name of the type being extended.
     *
     * @return string The name of the type being extended
     */
    public function getExtendedType();
}
