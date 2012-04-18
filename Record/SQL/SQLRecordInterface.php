<?php

/**
 * This file is part of the RollerworksRecordFilterBundle.
 *
 * (c) Sebastiaan Stok <s.stok@rollerscapes.net>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Rollerworks\RecordFilterBundle\Record\SQL;

use Rollerworks\RecordFilterBundle\Formatter\FormatterInterface;

/**
 * SQL Record Interface
 *
 * @author Sebastiaan Stok <s.stok@rollerscapes.net>
 */
interface SQLRecordInterface
{
    /**
     * Get the SQL WHERE Cases
     *
     * @return string
     */
    public function getWhereClause();
}