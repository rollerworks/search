<?php

/**
 * This file is part of the RollerworksRecordFilterBundle.
 *
 * (c) Rollerscapes
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU Lesser General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Lesser General Public License for more details.
 *
 * You should have received a copy of the GNU Lesser General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
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