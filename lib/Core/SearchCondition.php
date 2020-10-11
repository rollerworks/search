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
    private $primaryCondition;
    private $order;

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

    public function setPrimaryCondition(?SearchPrimaryCondition $condition): void
    {
        $this->primaryCondition = $condition;
    }

    public function getPrimaryCondition(): ?SearchPrimaryCondition
    {
        return $this->primaryCondition;
    }

    public function setOrder(?SearchOrder $order): void
    {
        $this->order = $order;
    }

    public function getOrder(): ?SearchOrder
    {
        return $this->order;
    }

    public function isEmpty(): bool
    {
        return \count($this->values->getGroups()) === 0 && \count($this->values->getFields()) === 0;
    }

    /**
     * Checks that the FieldSet of this condition is supported
     * by the contexts it's used in.
     *
     * @param string ...$name One or more FieldSet names to check for
     */
    public function assertFieldSetName(string ...$name): void
    {
        if (! \in_array($providedName = $this->fieldSet->getSetName(), $name, true)) {
            throw new UnsupportedFieldSetException($name, $providedName);
        }
    }
}
