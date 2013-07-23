<?php

/*
 * This file is part of the RollerworksRecordFilterBundle package.
 *
 * (c) Sebastiaan Stok <s.stok@rollerscapes.net>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Rollerworks\Bundle\RecordFilterBundle\Formatter;

use Rollerworks\Bundle\RecordFilterBundle\FieldSet;
use Rollerworks\Bundle\RecordFilterBundle\Input\InputInterface;

/**
 * RecordFiltering formatting interface.
 *
 * @author Sebastiaan Stok <s.stok@rollerscapes.net>
 *
 * @api
 */
interface FormatterInterface
{
    /**
     * Returns the formatted filters.
     *
     * This will return an array contain all the groups and there fields (per group).
     *
     * Like:
     * [group-n] => array(
     *   'field-name' => {\Rollerworks\Bundle\RecordFilterBundle\Value\FilterValuesBag object}
     * )
     *
     * @return array
     *
     * @api
     */
    public function getFilters();

    /**
     * Returns the FieldSet of the last performed formatting or null.
     *
     * @return FieldSet|null
     *
     * @api
     */
    public function getFieldSet();

    /**
     * Formats the input and returns either true or false.
     *
     * @param InputInterface $input
     *
     * @return boolean returns false on failure
     *
     * @api
     */
    public function formatInput(InputInterface $input);
}
