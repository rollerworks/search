<?php

/**
 * This file is part of the RollerworksRecordFilterBundle.
 *
 * (c) Rollerscapes
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @link    http://projects.rollerscapes.net/RollerFramework
 * @license http://www.opensource.org/licenses/lgpl-license.php LGPL
 */

namespace Rollerworks\RecordFilterBundle\Record\SQL;

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