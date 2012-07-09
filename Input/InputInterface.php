<?php

/**
 * This file is part of the RollerworksRecordFilterBundle.
 *
 * (c) Sebastiaan Stok <s.stok@rollerscapes.net>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Rollerworks\Bundle\RecordFilterBundle\Input;

use Rollerworks\Bundle\RecordFilterBundle\FieldSet;
use Rollerworks\Bundle\RecordFilterBundle\Value\FilterValuesBag;
use Rollerworks\Bundle\RecordFilterBundle\FilterField;

/**
 * Interface for supplying input-values.
 *
 * @author Sebastiaan Stok <s.stok@rollerscapes.net>
 */
interface InputInterface
{
    /**
     * Set the fieldSet.
     *
     * @param FieldSet $fields
     *
     * @api
     */
    public function setFieldSet(FieldSet $fields = null);

    /**
     * Set the configuration of an filter field.
     *
     * @param string      $name
     * @param FilterField $config
     *
     * @api
     */
    public function setField($name, FilterField $config);

    /**
     * Returns the groups and the containing filtering values.
     *
     * Values are not formatted nor validated.
     *
     * Returns the fields per group, like:
     * [group-n] => array('field-name' => {\Rollerworks\Bundle\RecordFilterBundle\Value\FilterValuesBag object})
     *
     * @return array|boolean Returns false in case of an error/failure
     *
     * @api
     */
    public function getGroups();

    /**
     * Returns the used FieldSet.
     *
     * @return FieldSet
     *
     * @api
     */
    public function getFieldSet();
}
