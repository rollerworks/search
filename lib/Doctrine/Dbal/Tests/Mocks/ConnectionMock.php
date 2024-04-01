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

namespace Rollerworks\Component\Search\Tests\Doctrine\Dbal\Mocks;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Platforms\AbstractPlatform;

class ConnectionMock extends Connection
{
    private $_fetchOneResult;
    private $_platform;
    private $_platformMock;
    private $_lastInsertId = 0;
    private $_inserts = [];

    public function __construct(array $params, $driver, $config = null, $eventManager = null)
    {
        $this->_platformMock = new DatabasePlatformMock();

        parent::__construct($params, $driver, $config, $eventManager);

        // Override possible assignment of platform to database platform mock
        $this->_platform = $this->_platformMock;
    }

    /**
     * @override
     */
    public function getDatabasePlatform(): AbstractPlatform
    {
        return $this->_platformMock;
    }

    /**
     * @override
     */
    public function insert($tableName, array $data, array $types = []): void
    {
        $this->_inserts[$tableName][] = $data;
    }

    /**
     * @override
     *
     * @param mixed|null $seqName
     */
    public function lastInsertId($seqName = null): false|int|string
    {
        return $this->_lastInsertId;
    }

    /**
     * @override
     */
    public function fetchColumn($statement, array $params = [], $column = 0, array $types = []): mixed
    {
        return $this->_fetchOneResult;
    }

    /**
     * @override
     *
     * @param mixed|null $type
     */
    public function quote($input, $type = null): mixed
    {
        if (\is_string($input)) {
            return "'" . $input . "'";
        }

        return $input;
    }

    /* Mock API */

    public function setFetchOneResult($fetchOneResult): void
    {
        $this->_fetchOneResult = $fetchOneResult;
    }

    public function setDatabasePlatform($platform): void
    {
        $this->_platformMock = $platform;
    }

    public function setLastInsertId($id): void
    {
        $this->_lastInsertId = $id;
    }

    public function getInserts(): array
    {
        return $this->_inserts;
    }

    public function reset(): void
    {
        $this->_inserts = [];
        $this->_lastInsertId = 0;
    }
}
