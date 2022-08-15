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

namespace Rollerworks\Component\Search\Doctrine\Dbal;

use Doctrine\DBAL\Types\Type as MappingType;
use Rollerworks\Component\Search\Doctrine\Dbal\Query\QueryField;
use Rollerworks\Component\Search\FieldSet;

/**
 * @internal
 */
final class FieldConfigurationSet
{
    private FieldSet $fieldSet;
    /** @var array<string, QueryField> */
    public $fields = [];

    public function __construct(FieldSet $fieldSet)
    {
        $this->fieldSet = $fieldSet;
    }

    public function setField(string $fieldName, string $column, string $alias = null, string $type = 'string'): void
    {
        $mappingIdx = null;

        if (mb_strpos($fieldName, '#') !== false) {
            [$fieldName, $mappingIdx] = explode('#', $fieldName, 2);
            unset($this->fields[$fieldName][null]);
        } else {
            $this->fields[$fieldName] = [];
        }

        $this->fields[$fieldName][$mappingIdx] = new QueryField(
            $fieldName . ($mappingIdx !== null ? "#{$mappingIdx}" : ''),
            $this->fieldSet->get($fieldName),
            MappingType::getType($type),
            $column,
            $alias
        );
    }
}
