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

use Rollerworks\Component\Search\Value\ValuesGroup;

/**
 * SearchCondition contains the searching conditions and FieldSet.
 */
class SearchCondition
{
    private $fieldSet;
    private $values;

    public function __construct(FieldSet $fieldSet, ValuesGroup $valuesGroup)
    {
        $this->fieldSet = $fieldSet;
        $this->values = $valuesGroup;
    }

    public function getFieldSet(): FieldSet
    {
        return $this->fieldSet;
    }

    public function getValuesGroup(): ValuesGroup
    {
        return $this->values;
    }
}
