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

namespace Rollerworks\Component\Search\Tests\Doctrine\Dbal;

use Doctrine\DBAL\Driver\PDOSqlite\Driver as PDOSqlite;
use Psr\SimpleCache\CacheInterface;
use Rollerworks\Component\Search\Doctrine\Dbal\DoctrineDbalFactory;
use Rollerworks\Component\Search\Extension\Core\Type\DateTimeType;
use Rollerworks\Component\Search\Extension\Core\Type\IntegerType;
use Rollerworks\Component\Search\Extension\Core\Type\TextType;
use Rollerworks\Component\Search\Extension\Doctrine\Dbal\DoctrineDbalExtension;
use Rollerworks\Component\Search\Field\OrderFieldType;
use Rollerworks\Component\Search\Test\SearchIntegrationTestCase;
use Rollerworks\Component\Search\Tests\Doctrine\Dbal\Mocks\ConnectionMock;
use Rollerworks\Component\Search\Tests\Doctrine\Dbal\Stub\Type\InvoiceLabelType;
use Rollerworks\Component\Search\Tests\Doctrine\Dbal\Stub\Type\InvoiceStatusType;

abstract class DbalTestCase extends SearchIntegrationTestCase
{
    protected function getFieldSet(bool $build = true)
    {
        $fieldSet = $this->getFactory()->createFieldSetBuilder();

        $fieldSet->add('id', IntegerType::class);
        $fieldSet->add('@id', OrderFieldType::class);
        $fieldSet->add('label', InvoiceLabelType::class);
        $fieldSet->add('status', InvoiceStatusType::class);

        $fieldSet->add('customer', IntegerType::class);
        $fieldSet->add('@customer', OrderFieldType::class);
        $fieldSet->add('customer_name', TextType::class);
        $fieldSet->add('customer_birthday', DateTimeType::class, ['allow_relative' => true]);

        return $build ? $fieldSet->getFieldSet('invoice') : $fieldSet;
    }

    protected function setUp(): void
    {
        parent::setUp();

        if (isset($_SERVER['DB_HOST'])) {
            $GLOBALS['db_host'] = $_SERVER['DB_HOST'];
        }

        if (isset($_SERVER['DB_PORT'])) {
            $GLOBALS['db_port'] = $_SERVER['DB_PORT'];
        }
    }

    protected function getExtensions(): array
    {
        return [new DoctrineDbalExtension()];
    }

    protected function getDbalFactory()
    {
        $cacheDriver = $this->getMockBuilder(CacheInterface::class)->getMock();

        return new DoctrineDbalFactory($cacheDriver);
    }

    /**
     * @return ConnectionMock
     */
    protected function getConnectionMock()
    {
        return new ConnectionMock([], new PDOSqlite());
    }
}
