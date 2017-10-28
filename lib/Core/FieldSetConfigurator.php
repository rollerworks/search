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

namespace Rollerworks\Component\Search;

/**
 * A FieldSetConfigurator configures a FieldSetBuilder instance.
 *
 * The purpose of a configurator is to allow re-usage of a FieldSet.
 * And allow for name expectations (allow only a limited specific FieldSets).
 *
 * When you want to combine configurators, simply use PHP inheritance and
 * traits.
 *
 * @author Sebastiaan Stok <s.stok@rollerscapes.net>
 */
interface FieldSetConfigurator
{
    /**
     * Configure the FieldSet builder.
     *
     * @param FieldSetBuilder $builder
     */
    public function buildFieldSet(FieldSetBuilder $builder): void;
}
