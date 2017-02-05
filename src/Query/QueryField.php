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

namespace Rollerworks\Component\Search\Doctrine\Dbal\Query;

use Doctrine\DBAL\Types\Type as DbType;
use Rollerworks\Component\Search\Doctrine\Dbal\ConversionStrategyInterface;
use Rollerworks\Component\Search\Doctrine\Dbal\SqlFieldConversionInterface;
use Rollerworks\Component\Search\Doctrine\Dbal\SqlValueConversionInterface;
use Rollerworks\Component\Search\Doctrine\Dbal\ValueConversionInterface;
use Rollerworks\Component\Search\Field\FieldConfig;

class QueryField
{
    protected $fieldConfig;
    protected $dbType;
    protected $alias;
    protected $column;
    protected $resolvedColumn;
    protected $fieldConversion;
    protected $valueConversion;

    public function __construct(
        FieldConfig $fieldConfigInterface,
        DbType $dbType,
        $alias,
        $column,
        $fieldConversion,
        $valueConversion
    ) {
        $this->fieldConfig = $fieldConfigInterface;
        $this->dbType = $dbType;
        $this->alias = $alias;
        $this->column = $column;
        $this->resolvedColumn = ($alias ? $alias.'.' : '').$column;
        $this->fieldConversion = $fieldConversion;
        $this->valueConversion = $valueConversion;
    }

    public function getFieldConfig(): FieldConfig
    {
        return $this->fieldConfig;
    }

    public function getDbType(): DbType
    {
        return $this->dbType;
    }

    public function getAlias(): string
    {
        return $this->alias;
    }

    public function getColumn(bool $withAlias = true): string
    {
        return $withAlias ? $this->resolvedColumn : $this->column;
    }

    /**
     * @return SqlFieldConversionInterface|ConversionStrategyInterface
     */
    public function getFieldConversion()
    {
        return $this->fieldConversion;
    }

    /**
     * @return ConversionStrategyInterface|ValueConversionInterface|SqlValueConversionInterface
     */
    public function getValueConversion()
    {
        return $this->valueConversion;
    }

    /**
     * @return bool
     */
    public function hasConversionStrategy(): bool
    {
        return $this->fieldConversion instanceof ConversionStrategyInterface || $this->valueConversion instanceof ConversionStrategyInterface;
    }
}
