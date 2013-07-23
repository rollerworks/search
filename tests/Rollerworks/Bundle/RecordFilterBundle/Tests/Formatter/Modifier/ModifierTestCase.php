<?php

/*
 * This file is part of the RollerworksRecordFilterBundle package.
 *
 * (c) Sebastiaan Stok <s.stok@rollerscapes.net>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Rollerworks\Bundle\RecordFilterBundle\Tests\Formatter\Modifier;

use Rollerworks\Bundle\RecordFilterBundle\Formatter\ModifierFormatter as Formatter;
use Rollerworks\Bundle\RecordFilterBundle\Formatter\Modifier\Validator;
use Rollerworks\Bundle\RecordFilterBundle\Formatter\Modifier\DuplicateRemove;
use Rollerworks\Bundle\RecordFilterBundle\Formatter\Modifier\ValuesToRange;
use Rollerworks\Bundle\RecordFilterBundle\Formatter\Modifier\RangeNormalizer;
use Rollerworks\Bundle\RecordFilterBundle\Formatter\Modifier\CompareNormalizer;
use Rollerworks\Bundle\RecordFilterBundle\Formatter\Modifier\ValueOptimizer;

abstract class ModifierTestCase extends \Rollerworks\Bundle\RecordFilterBundle\Tests\TestCase
{
    /**
     * Returns an new Formatter object.
     *
     * @param boolean $loadModifiers
     *
     * @return Formatter
     */
    protected function newFormatter($loadModifiers = true)
    {
        \Locale::setDefault('nl');

        $formatter = new Formatter($this->translator);

        if ($loadModifiers) {
            $formatter->registerModifier(new Validator($this->translator));
            $formatter->registerModifier(new DuplicateRemove());
            $formatter->registerModifier(new RangeNormalizer());
            $formatter->registerModifier(new ValuesToRange());
            $formatter->registerModifier(new CompareNormalizer());
            $formatter->registerModifier(new ValueOptimizer());
        }

        return $formatter;
    }
}
