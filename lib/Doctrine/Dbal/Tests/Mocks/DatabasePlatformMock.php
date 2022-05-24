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

use Doctrine\DBAL\Exception as DBALException;
use Doctrine\DBAL\Platforms\AbstractPlatform;

class DatabasePlatformMock extends AbstractPlatform
{
    private $_sequenceNextValSql = '';
    private $_prefersIdentityColumns = true;
    private $_prefersSequences = false;

    /**
     * @override
     */
    public function prefersIdentityColumns(): bool
    {
        return $this->_prefersIdentityColumns;
    }

    /**
     * @override
     */
    public function prefersSequences(): bool
    {
        return $this->_prefersSequences;
    }

    /** @override */
    public function getSequenceNextValSQL($sequenceName): string
    {
        return $this->_sequenceNextValSql;
    }

    /** @override */
    public function getBooleanTypeDeclarationSQL(array $field): string
    {
    }

    /** @override */
    public function getIntegerTypeDeclarationSQL(array $field): string
    {
    }

    /** @override */
    public function getBigIntTypeDeclarationSQL(array $field): string
    {
    }

    /** @override */
    public function getSmallIntTypeDeclarationSQL(array $field): string
    {
    }

    /** @override */
    protected function _getCommonIntegerTypeDeclarationSQL(array $columnDef): string
    {
    }

    /** @override */
    public function getVarcharTypeDeclarationSQL(array $field): string
    {
    }

    /** @override */
    public function getClobTypeDeclarationSQL(array $field): string
    {
    }

    /* MOCK API */

    public function setPrefersIdentityColumns($bool): void
    {
        $this->_prefersIdentityColumns = $bool;
    }

    public function setPrefersSequences($bool): void
    {
        $this->_prefersSequences = $bool;
    }

    public function setSequenceNextValSql($sql): void
    {
        $this->_sequenceNextValSql = $sql;
    }

    public function getName(): string
    {
        return 'mock';
    }

    protected function initializeDoctrineTypeMappings(): void
    {
    }

    protected function getVarcharTypeDeclarationSQLSnippet($length, $fixed): string
    {
    }

    /**
     * Gets the SQL Snippet used to declare a BLOB column type.
     *
     * @throws DBALException
     */
    public function getBlobTypeDeclarationSQL(array $field): void
    {
        throw DBALException::notSupported(__METHOD__);
    }
}
