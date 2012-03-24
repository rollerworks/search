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
 * Interface for supplying input-values
 *
 * @author Sebastiaan Stok <s.stok@rollerscapes.net>
 */
interface InputInterface
{
    /**
     * Get the input-values by field.
     * The values are not formatted or validated.
     *
     * Returns the fields per group, like:
     * [group-n] => array('field-name' => 'values')
     *
     * Depending on hasGroups(), the number of groups varies.
     * When there are no values, an empty array is returned.
     *
     * @return array
     */
    public function getValues();

    /**
     * Returns whether the value list is an or-case.
     *
     * @return boolean
     */
    public function hasGroups();
}