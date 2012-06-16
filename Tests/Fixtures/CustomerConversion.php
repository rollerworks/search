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

class CustomerConversion implements \Rollerworks\Bundle\RecordFilterBundle\Record\Sql\SqlValueConversionInterface
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
    public function convertValue($input, DBALType $type, Connection $connection, $isDql, array $params = array())
    {
        return $input->getCustomerId();
    }
}
