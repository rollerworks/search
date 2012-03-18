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

namespace Rollerworks\RecordFilterBundle\Input;

/**
 * Input bases Class.
 *
 * Provide basic functionality for an Input Class.
 *
 * @author Sebastiaan Stok <s.stok@rollerscapes.net>
 */
abstract class AbstractInput implements InputInterface
{
    /**
     * Whether the input has an OR-list.
     *
     * @var boolean
     */
    protected $hasGroups = false;

    /**
     * Values per field.
     *
     * The value is stored as an string.
     *
     * Internal storage: field-name => value
     *
     * @var array
     */
    protected $groups = array();

    /**
     * Get the input-values.
     *
     * The values are un-formatted or validated
     *
     * @return array
     */
    public function getValues()
    {
        return $this->groups;
    }

    /**
     * Returns whether the value list has groups.
     *
     * @return boolean
     */
    public function hasGroups()
    {
        return $this->hasGroups;
    }
}