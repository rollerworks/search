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

namespace Rollerworks\RecordFilterBundle\Formatter\Modifier;

use Rollerworks\RecordFilterBundle\Formatter\FormatterInterface;

/**
 * Pre modifier interface.
 *
 * Must be in implemented by the Formatter Pre-modifier.
 *
 * @author Sebastiaan Stok <s.stok@rollerscapes.net>
 */
interface PreModifierInterface
{
    /**
     * Returns the name of the modifier.
     * This would normally be the class-name in lowercase and underscored.
     *
     * @return string
     */
    public function getModifierName();

    /**
     * Modify the filters and returns them.
     * Like: name => [value1, value2]
     *
     * Returns the modified filter list.
     *
     * @param FormatterInterface    $formatter
     * @param array                 $filters
     * @param integer               $groupIndex
     * @return array
     */
    public function modFilters(FormatterInterface $formatter, $filters, $groupIndex);
}