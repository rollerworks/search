<?php

/**
 * This file is part of the RollerworksRecordFilterBundle.
 *
 * (c) Sebastiaan Stok <s.stok@rollerscapes.net>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Rollerworks\RecordFilterBundle\Formatter;

/**
 * RecordFiltering formatting interface.
 *
 * @author Sebastiaan Stok <s.stok@rollerscapes.net>
 */
interface FormatterInterface
{
    /**
     * Returns the filters to apply on a Record-Formatter.
     *
     * This will return an array contain all the groups and there fields (per group).
     *
     * Like:
     * [group-n] => array(
     *   'field-name' => {\Rollerworks\RecordFilterBundle\Value\FilterValuesBag object}
     * )
     *
     * @return array
     *
     * @api
     */
    public function getFilters();
}
