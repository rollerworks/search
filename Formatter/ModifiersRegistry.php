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

use Rollerworks\RecordFilterBundle\Formatter\FilterConfig;
use Rollerworks\RecordFilterBundle\Formatter\FormatterInterface;
use Rollerworks\RecordFilterBundle\Formatter\Modifier\PreModifierInterface;
use Rollerworks\RecordFilterBundle\Formatter\Modifier\PostModifierInterface;
use Rollerworks\RecordFilterBundle\FilterStruct;

/**
 * The Modifiers Registry keeps all the registered pre and post modifiers.
 *
 * Modifiers (per type) are executed in order of there registration.
 *
 * @author Sebastiaan Stok <s.stok@rollerscapes.net>
 */
class ModifiersRegistry
{
    /**
     * @var array
     */
    protected $preModifiers = array();

    /**
     * @var array
     */
    protected $postModifiers = array();

    /**
     * Register an pre-modifier instance.
     * This will be executed before the filters to object conversion.
     *
     * @param PreModifierInterface $modifier
     * @return ModifiersRegistry
     *
     * @api
     */
    public function registerPreModifier(PreModifierInterface $modifier)
    {
        $this->preModifiers[ $modifier->getModifierName() ] = $modifier;

        return $this;
    }

    /**
     * Register an post-modifier instance.
     * This will be executed after the filters to object conversion.
     *
     * @param PostModifierInterface $modifier
     * @return ModifiersRegistry
     *
     * @api
     */
    public function registerPostModifier(PostModifierInterface $modifier)
    {
        $this->postModifiers[ $modifier->getModifierName() ] = $modifier;

        return $this;
    }

    /**
     * Returns the registered pre modifiers.
     * Like: modifier-name => (Modifier Object)
     *
     * @return array
     *
     * @api
     */
    public function getPreModifiers()
    {
        return $this->preModifiers;
    }

    /**
     * Returns the registered pre modifiers.
     * Like: modifier-name => (Modifier Object)
     *
     * @return array
     *
     * @api
     */
    public function getPostModifiers()
    {
        return $this->postModifiers;
    }
}
