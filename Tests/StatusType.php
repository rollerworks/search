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

namespace Rollerworks\RecordFilterBundle\Tests;

use Rollerworks\RecordFilterBundle\Formatter\FilterType;
use Rollerworks\RecordFilterBundle\Formatter\OptimizableInterface;
use Rollerworks\RecordFilterBundle\FilterStruct;

class StatusType implements FilterType, OptimizableInterface
{
    /**
     * {@inheritdoc}
     */
    public function sanitizeString($input)
    {
        $replacement      = array('Active', 'Not-active', 'Removed');
        $replacementValue = array(1, 0, -1);

        return str_replace($replacement, $replacementValue, mb_strtolower($input));
    }

    /**
     * {@inheritdoc}
     */
    public function isHigher($input, $nextValue)
    {
        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function isLower($input, $nextValue)
    {
        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function isEquals($input, $nextValue)
    {
        return ($input === $nextValue);
    }

    /**
     * {@inheritdoc}
     */
    public function validateValue($input, &$message = null)
    {
        $message = 'This is not an valid status';

        $input = $this->sanitizeString($input);

        return in_array($input, array(1, 0, -1));
    }

    /**
     * {@inheritdoc}
     */
    public function optimizeField(FilterStruct $field, &$paMessage)
    {
        // Since there are no duplicates and only three values are legal.
        return (count($field->getSingleValues()) === 3 ? null : true);
    }
}