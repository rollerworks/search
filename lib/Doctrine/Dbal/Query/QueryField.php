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
use Rollerworks\Component\Search\Doctrine\Dbal\ColumnConversion;
use Rollerworks\Component\Search\Doctrine\Dbal\ValueConversion;
use Rollerworks\Component\Search\Field\FieldConfig;

/**
 * Holds the mapping information of a search field for Doctrine DBAL.
 *
 * Information is provided in public properties for better performance.
 * This information is read-only and should not be changed afterwards.
 *
 * @author Sebastiaan Stok <s.stok@rollerscapes.net>
 */
class QueryField implements \Serializable
{
    /**
     * @var string
     */
    public $mappingName;

    /**
     * @var FieldConfig
     */
    public $fieldConfig;

    /**
     * @var DbType
     */
    public $dbType;

    /**
     * @var string
     */
    public $column;

    /**
     * @var ColumnConversion|null
     */
    public $columnConversion;

    /**
     * @var ValueConversion|null
     */
    public $valueConversion;

    /**
     * @var string
     */
    public $alias;

    /**
     * @var string
     */
    public $tableColumn;

    public function __construct(string $mappingName, FieldConfig $fieldConfig, DbType $dbType, string $column, string $alias = null)
    {
        $this->mappingName = $mappingName;
        $this->fieldConfig = $fieldConfig;

        $this->alias = $alias;
        $this->tableColumn = $column;
        $this->column = ($alias ? $alias . '.' : '') . $column;
        $this->dbType = $dbType;

        $this->initConversions($fieldConfig);
    }

    public function serialize()
    {
        return \serialize(
            [
                'mapping_name' => $this->mappingName,
                'field' => $this->fieldConfig->getName(),
                'db_type' => $this->dbType->getName(),
            ]
        );
    }

    public function unserialize($serialized): void
    {
        // noop
    }

    protected function initConversions(FieldConfig $fieldConfig): void
    {
        $converter = $fieldConfig->getOption('doctrine_dbal_conversion');

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
