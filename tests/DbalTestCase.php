<?php

/*
 * This file is part of the RollerworksSearch Component package.
 *
 * (c) Sebastiaan Stok <s.stok@rollerscapes.net>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Rollerworks\Component\Search\Tests\Doctrine\Dbal;

use Doctrine\DBAL\Driver\PDOSqlite\Driver as PDOSqlite;
use Rollerworks\Component\Search\Doctrine\Dbal\DoctrineDbalFactory;
use Rollerworks\Component\Search\Doctrine\Dbal\WhereBuilder;
use Rollerworks\Component\Search\Extension\Doctrine\Dbal\DoctrineDbalExtension;
use Rollerworks\Component\Search\Test\SearchIntegrationTestCase;
use Rollerworks\Component\Search\Tests\Doctrine\Dbal\Stub\Type\InvoiceLabelType;
use Rollerworks\Component\Search\Tests\Doctrine\Dbal\Stub\Type\InvoiceStatusType;
use Rollerworks\Component\Search\Tests\Mocks\ConnectionMock;

abstract class DbalTestCase extends SearchIntegrationTestCase
{
    protected function getFieldSet($build = true)
    {
        $fieldSet = $this->getFactory()->createFieldSetBuilder('invoice');

        $fieldSet->add('id', 'integer');
        $fieldSet->add('label', 'invoice_label');
        $fieldSet->add('status', 'invoice_status');

        $fieldSet->add('customer', 'integer');
        $fieldSet->add('customer_name', 'text');
        $fieldSet->add('customer_birthday', 'date');

        return $build ? $fieldSet->getFieldSet() : $fieldSet;
    }

    protected function getExtensions()
    {
        return array(new DoctrineDbalExtension());
    }

    protected function getTypes()
    {
        return array(
            new InvoiceLabelType(),
            new InvoiceStatusType()
        );
    }

    protected function getDbalFactory()
    {
        $cacheDriver = $this->getMock('Doctrine\Common\Cache\Cache');

        return new DoctrineDbalFactory($cacheDriver);
    }

    /**
     * @return ConnectionMock
     */
    protected function getConnectionMock()
    {
        return new ConnectionMock(array(), new PDOSqlite());
    }
}
