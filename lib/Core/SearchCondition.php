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

use Rollerworks\Component\Search\Exception\UnsupportedFieldSetException;
use Rollerworks\Component\Search\Value\ValuesGroup;

/**
 * SearchCondition contains the searching conditions and FieldSet.
 *
 * @author Sebastiaan Stok <s.stok@rollerscapes.net>
 */
class SearchCondition
{
    private $fieldSet;
    private $values;
    private $preCondition;

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

    public function setPreCondition(SearchPreCondition $condition): void
    {
        $this->preCondition = $condition;
    }

    public function getPreCondition(): ?SearchPreCondition
    {
        return $this->preCondition;
    }

    /**
     * Checks that the FieldSet of this condition is supported
     * by the contexts it's used in.
     *
     * @param \string[] ...$name One or more FieldSet names to check for
     */
    public function assertFieldSetName(string ...$name)
    {
        if (!in_array($providedName = $this->fieldSet->getSetName(), $name, true)) {
            throw new UnsupportedFieldSetException($name, $providedName);
        }
    }
}
