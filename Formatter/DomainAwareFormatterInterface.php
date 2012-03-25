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
 * DomainAwareFormatter interface should be implemented by formatter classes that are domain-aware.
 *
 * An formatter is domain-aware when the configuration only applies to one class (domain).
 *
 * @author Sebastiaan Stok <s.stok@rollerscapes.net>
 */
interface DomainAwareFormatterInterface extends FormatterInterface
{
    /**
     * Returns the class name from which the configuration was read.
     *
     * @return string
     */
    public function getBaseClassName();
}