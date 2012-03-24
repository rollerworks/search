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

use Rollerworks\RecordFilterBundle\FilterStruct;
use Rollerworks\RecordFilterBundle\Formatter\OptimizableInterface;
use Rollerworks\RecordFilterBundle\Formatter\FilterConfig;
use Rollerworks\RecordFilterBundle\Formatter\FormatterInterface;

/**
 * Optimizes the value by there own implementation.
 *
 * @author Sebastiaan Stok <s.stok@rollerscapes.net>
 */
class ValueOptimizer implements PostModifierInterface
{
    /**
     * Optimizer messages
     *
     * @var array
     */
    protected $messages = array();

    /**
     * {@inheritdoc}
     */
    public function getModifierName()
    {
        return 'valueOptimizer';
    }

    /**
     * {@inheritdoc}
     */
    public function modFilters(FormatterInterface $formatter, FilterConfig $filterConfig, FilterStruct $filterStruct, $groupIndex)
    {
        $this->messages = array();

        if ($filterConfig->hasType() && $filterConfig->getType() instanceof OptimizableInterface) {
            return $filterConfig->getType()->optimizeField($filterStruct, $this->messages);
        }
        else {
            return true;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getMessages()
    {
        return $this->messages;
    }
}
