<?php

/**
 * This file is part of the RollerworksRecordFilterBundle.
 *
 * (c) Sebastiaan Stok <s.stok@rollerscapes.net>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Rollerworks\Bundle\RecordFilterBundle\Tests\Fixtures;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Types\Type as DBALType;

use \Rollerworks\Bundle\RecordFilterBundle\Doctrine\Orm\SqlValueConversionInterface;
use \Rollerworks\Bundle\RecordFilterBundle\Doctrine\Orm\SqlFieldConversionInterface;

class CustomerConversion implements SqlValueConversionInterface, SqlFieldConversionInterface
{
    /**
     * {@inheritdoc}
     */
    public function requiresBaseConversion()
    {
        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function convertValue($input, DBALType $type, Connection $connection, array $params = array())
    {
        return intval($input->getCustomerId());
    }

    /**
     * {@inheritdoc}
     */
    public function getConvertFieldSql($fieldName, DBALType $type, Connection $connection, array $params = array())
    {
        return "CAST($fieldName AS customer_type)";
    }
}
