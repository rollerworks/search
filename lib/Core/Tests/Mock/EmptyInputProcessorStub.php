<?php

declare(strict_types=1);

/*
 * This file is part of the RollerworksSearch package.
 *
 * (c) Sebastiaan Stok <s.stok@rollerscapes.net>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Rollerworks\Component\Search\Tests\Mock;

use Rollerworks\Component\Search\Input\ProcessorConfig;
use Rollerworks\Component\Search\InputProcessor;
use Rollerworks\Component\Search\SearchCondition;
use Rollerworks\Component\Search\Value\ValuesGroup;

final class EmptyInputProcessorStub implements InputProcessor
{
    public function process(ProcessorConfig $config, $input): SearchCondition
    {
        $valuesGroup = new ValuesGroup();

        return new SearchCondition(new FieldSetStub(), $valuesGroup);
    }
}
