<?php

/**
 * This file is part of RollerworksSearch Component package.
 *
 * (c) 2012-2014 Sebastiaan Stok <s.stok@rollerscapes.net>
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
     * @return FieldSet
     */
    public function getFieldSet();

    /**
     * @return ValuesGroup
     */
    public function getValuesGroup();
}
