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

use Rollerworks\Component\Search\Exception\InvalidArgumentException;
use Rollerworks\Component\Search\Value\ValuesGroup;

/**
 * @author Dalibor Karlović <dalibor@flexolabs.io>
 */
final class SearchOrder
{
    private $values;

    public function __construct(ValuesGroup $valuesGroup)
    {
        if ($valuesGroup->hasGroups()) {
            throw new InvalidArgumentException('A SearchOrder must have a single-level structure. Only fields with single values are accepted.');
        }

        $this->values = $valuesGroup;
    }

    public function getValuesGroup(): ValuesGroup
    {
        return $this->values;
    }

    /**
     * @return array<string, string>
     */
    public function getFields(): array
    {
        $fields = [];

        foreach ($this->values->getFields() as $fieldName => $valuesBag) {
            $direction = \strtolower(\current($valuesBag->getSimpleValues()));
            \assert($direction === 'desc' || $direction === 'asc');

            $fields[$fieldName] = $direction;
        }

        return $fields;
    }
}
