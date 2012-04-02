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

/**
 * DomainAwareSQLRecord interface should be implemented by SQL record classes that are domain-aware.
 *
 * An class is domain-aware when the configuration only applies to one class (domain).
 *
 * @author Sebastiaan Stok <s.stok@rollerscapes.net>
 */
interface DomainAwareSQLRecordInterface extends SQLRecordInterface
{
    /**
     * Returns the class name from which the configuration was read.
     *
     * @return string
     */
    public function getBaseClassName();
}