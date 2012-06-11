<?php

/**
 * This file is part of the RollerworksRecordFilterBundle.
 *
 * (c) Sebastiaan Stok <s.stok@rollerscapes.net>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Rollerworks\RecordFilterBundle\Input;

use Rollerworks\RecordFilterBundle\FieldSet;
use Rollerworks\RecordFilterBundle\Type\FilterTypeInterface;
use Rollerworks\RecordFilterBundle\Value\FilterValuesBag;

/**
 * Interface for supplying input-values
 *
 * @author Sebastiaan Stok <s.stok@rollerscapes.net>
 */
interface InputInterface
{
    /**
     * Set the configuration of an filter field.
     *
     * Field-name is always converted to lowercase
     *
     * @param string                   $fieldName
     * @param string                   $label
     * @param FilterTypeInterface|null $valueType
     * @param boolean                  $required
     * @param boolean                  $acceptRanges
     * @param boolean                  $acceptCompares
     *
     * @return InputInterface
     *
     * @api
     *
     * FIXME Refactor to be compatible with FieldSet
     */
    public function setField($fieldName, $label = null, FilterTypeInterface $valueType = null, $required = false, $acceptRanges = false, $acceptCompares = false);

    /**
     * Returns the groups and the containing filtering values.
     *
     * The values are not formatted or validated.
     *
     * Returns the fields per group, like:
     * [group-n] => array('field-name' => {\Rollerworks\RecordFilterBundle\Value\FilterValuesBag object})
     *
     * @return array
     *
     * @api
     */
    public function getGroups();

    /**
     * Returns all the configured fields and there configuration.
     *
     * @return FieldSet
     *
     * @api
     */
    public function getFieldsConfig();
}
