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

namespace Rollerworks\RecordFilterBundle\Tests\Modifier;

use Rollerworks\RecordFilterBundle\Formatter\Formatter;
use Rollerworks\RecordFilterBundle\Formatter\Modifier\Validator;
use Rollerworks\RecordFilterBundle\Formatter\Modifier\DuplicateRemove;
use Rollerworks\RecordFilterBundle\Formatter\Modifier\ListToRange;
use Rollerworks\RecordFilterBundle\Formatter\Modifier\RangeNormalizer;
use Rollerworks\RecordFilterBundle\Formatter\Modifier\CompareNormalizer;
use Rollerworks\RecordFilterBundle\Formatter\Modifier\ValueOptimizer;

abstract class TestCase extends \Rollerworks\RecordFilterBundle\Tests\TestCase
{
    /**
     * @param bool $loadModifiers
     * @return \Rollerworks\RecordFilterBundle\Formatter\Formatter
     */
    protected function newFormatter($loadModifiers = true)
    {
        $formatter = new Formatter($this->translator);

        if ($loadModifiers) {
            $formatter->registerPostModifier(new Validator());
            $formatter->registerPostModifier(new DuplicateRemove());
            $formatter->registerPostModifier(new ListToRange());
            $formatter->registerPostModifier(new RangeNormalizer());
            $formatter->registerPostModifier(new CompareNormalizer());
            $formatter->registerPostModifier(new ValueOptimizer());
        }

        return $formatter;
    }
}