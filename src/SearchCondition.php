<?php

/*
 * This file is part of the Rollerworks Search Component package.
 *
 * (c) Sebastiaan Stok <s.stok@rollerscapes.net>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
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
