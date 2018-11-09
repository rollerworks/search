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
 *
 * And provide support for name expectations (to allow only specific FieldSets)
 * using {@link \Rollerworks\Component\Search\SearchCondition::assertFieldSetName}.
 *
 * @author Sebastiaan Stok <s.stok@rollerscapes.net>
 */
interface FieldSetConfigurator
{
    /**
     * Configures the FieldSet builder.
     */
    public function buildFieldSet(FieldSetBuilder $builder): void;
}
