<?php

/*
 * This file is part of the RollerworksSearch package.
 *
 * (c) Sebastiaan Stok <s.stok@rollerscapes.net>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Rollerworks\Component\Search\Tests\Doctrine\Dbal;

use Doctrine\DBAL\Connection;

final class SchemaRecord
{
    private $table;
    private $records = [];
    private $columns = [];

    public function __construct($tableName, array $columns)
    {
        $this->table = $tableName;
        $this->columns = $columns;
    }

    /**
     * @param string $tableName Fully qualified table-name
     * @param array  $columns   [column1, column2] (must contain "id")
     *
     * @return SchemaRecord
     */
    public static function create($tableName, array $columns)
    {
        return new self($tableName, $columns);
    }

    public function getTable()
    {
        return $this->table;
    }

    public function add(array $values)
    {
        if (count($values) != count($this->columns)) {
            throw new \InvalidArgumentException(
                sprintf(
                    'Values count mismatch, expected %d got %d on table "%s" with record: %s',
                    count($this->columns),
                    count($values),
                    $this->table,
                    var_export($values, true)
                )
            );
        }

        $this->records[] = $values;

        return $this;
    }

    /**
     * Semantic method for chaining.
     *
     * @return $this
     */
    public function end()
    {
        return $this;
    }

    /**
     * Semantic method for chaining.
     *
     * @return $this
     */
    public function records()
    {
        return $this;
    }

    public function getColumns()
    {
        return $this->columns;
    }

    public function executeRecords(Connection $connection)
    {
        foreach ($this->records as $values) {
            $connection->insert(
                $this->table,
                array_combine(array_keys($this->columns), $values),
                $this->columns
            );
        }
    }
}
