<?php

namespace Rollerworks\Component\Search\Doctrine\Dbal\Query;

use Doctrine\DBAL\Types\Type as DbType;
use Rollerworks\Component\Search\Doctrine\Dbal\ConversionStrategyInterface;
use Rollerworks\Component\Search\Doctrine\Dbal\SqlFieldConversionInterface;
use Rollerworks\Component\Search\Doctrine\Dbal\SqlValueConversionInterface;
use Rollerworks\Component\Search\Doctrine\Dbal\ValueConversionInterface;
use Rollerworks\Component\Search\FieldConfigInterface;

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
        FieldConfigInterface $fieldConfigInterface,
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

    /**
     * @return FieldConfigInterface
     */
    public function getFieldConfig()
    {
        return $this->fieldConfig;
    }

    /**
     * @return DbType
     */
    public function getDbType()
    {
        return $this->dbType;
    }

    public function getAlias()
    {
        return $this->alias;
    }

    public function getColumn($withAlias = true)
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
    public function hasConversionStrategy()
    {
        return $this->fieldConversion instanceof ConversionStrategyInterface || $this->valueConversion instanceof ConversionStrategyInterface;
    }
}
