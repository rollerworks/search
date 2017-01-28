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
 * @author Sebastiaan Stok <s.stok@rollerscapes.net>
 */
interface FieldSetWithView extends FieldSet
{
    /**
     * Create a new FieldSetView instance of the FieldSet.
     *
     * @return FieldSetView
     */
    public function createView(): FieldSetView;
}
