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
use Rollerworks\RecordFilterBundle\FilterConfig;

/**
 * Interface for supplying input-values
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
     * @param string       $name
     * @param FilterConfig $config
     *
     * @api
     */
    public function setField($name, FilterConfig $config);

    /**
     * Returns the groups and the containing filtering values.
     *
     * The values are not formatted or validated.
     *
     * Returns the fields per group, like:
     * [group-n] => array('field-name' => {\Rollerworks\RecordFilterBundle\Value\FilterValuesBag object})
     *
     * @return array|boolean Returns false in case of an error/failure
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
