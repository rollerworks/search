<?php

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
 * SearchCondition contains the searching conditions and FieldSet.
 */
interface SearchConditionInterface
{
    /**
     * Returns the configured FieldSet of the search condition.
     *
     * @return FieldSet
     */
    public function getFieldSet();

    /**
     * Returns the root ValuesGroup of the search condition.
     *
     * @return ValuesGroup
     */
    public function getValuesGroup();
}
