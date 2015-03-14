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
class SearchCondition implements SearchConditionInterface
{
    private $fieldSet;
    private $values;

    /**
     * @param FieldSet    $fieldSet
     * @param ValuesGroup $valuesGroup
     */
    public function __construct(FieldSet $fieldSet, ValuesGroup $valuesGroup)
    {
        $this->fieldSet = $fieldSet;
        $this->values = $valuesGroup;
    }

    /**
     * @return FieldSet
     */
    public function getFieldSet()
    {
        return $this->fieldSet;
    }

    /**
     * @return ValuesGroup
     */
    public function getValuesGroup()
    {
        return $this->values;
    }
}
