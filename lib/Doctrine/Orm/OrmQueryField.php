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

namespace Rollerworks\Component\Search\Doctrine\Orm;

use Rollerworks\Component\Search\Doctrine\Dbal\Query\QueryField;
use Rollerworks\Component\Search\Field\FieldConfig;

/**
 * @property ColumnConversion|null $columnConversion
 * @property ValueConversion|null  $valueConversion
 */
final class OrmQueryField extends QueryField
{
    public string $entity;

    public function __construct(string $mappingName, FieldConfig $fieldConfig, string $dbType, string $column, string $alias, string $entity)
    {
        parent::__construct(
            $mappingName,
            $fieldConfig,
            $dbType,
            $column,
            $alias
        );

        $this->entity = $entity;
    }

    protected function initConversions(FieldConfig $fieldConfig): void
    {
        $converter = $fieldConfig->getOption('doctrine_orm_conversion');

        if ($converter instanceof \Closure) {
            $converter = $converter();
        }

        if ($converter instanceof ColumnConversion) {
            $this->columnConversion = $converter;
        }

        if ($converter instanceof ValueConversion) {
            $this->valueConversion = $converter;
        }
    }
}
