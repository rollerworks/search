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

namespace Rollerworks\RecordFilterBundle\Formatter;

use Symfony\Component\DependencyInjection\ContainerAwareInterface;

/**
 * RecordFiltering formatting interface.
 *
 * @author Sebastiaan Stok <s.stok@rollerscapes.net>
 */
interface FormatterInterface extends ContainerAwareInterface
{
    /**
     * Returns the filters to apply on a Record-Formatter.
     *
     * This will return an array contain all the groups and there fields (per group).
     *
     * Like:
     * [group-n] => array(
     *   'field-name' => {\Rollerworks\RecordFilterBundle\FilterStruct object}
     * )
     *
     * The FilterStruct contains all the filtering information of the field.
     *
     * @return array
     *
     * @api
     */
    public function getFilters();

    /**
     * Returns whether the value list is an or-case.
     *
     * @return boolean
     *
     * @api
     */
    public function hasGroups();
}