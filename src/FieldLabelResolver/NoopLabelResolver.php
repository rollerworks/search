<?php

/*
 * This file is part of the RollerworksSearch package.
 *
 * (c) Sebastiaan Stok <s.stok@rollerscapes.net>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Rollerworks\Component\Search\FieldLabelResolver;

use Rollerworks\Component\Search\FieldLabelResolverInterface;
use Rollerworks\Component\Search\FieldSet;

class NoopLabelResolver implements FieldLabelResolverInterface
{
    /**
     * {@inheritdoc}
     */
    public function resolveFieldLabel(FieldSet $fieldSet, $fieldName)
    {
        return $fieldName;
    }
}
