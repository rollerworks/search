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

namespace Rollerworks\Component\Search\Input;

use Rollerworks\Component\Search\ErrorList;
use Rollerworks\Component\Search\Exception\InputProcessorException;
use Rollerworks\Component\Search\Exception\InvalidSearchConditionException;
use Rollerworks\Component\Search\Exception\StringLexerException;
use Rollerworks\Component\Search\Exception\UnexpectedTypeException;
use Rollerworks\Component\Search\Exception\UnknownFieldException;
use Rollerworks\Component\Search\Field\FieldConfig;
use Rollerworks\Component\Search\FieldSet;
use Rollerworks\Component\Search\SearchCondition;
use Rollerworks\Component\Search\Value\ValuesBag;
use Rollerworks\Component\Search\Value\ValuesGroup;

/**
 * NormStringQueryInput - processes input in the StringInput syntax
 * using the Normalized value format.
 */
final class NormStringQueryInput extends StringInput
{
    protected function initForProcess(ProcessorConfig $config): void
    {
        $this->fields = $this->resolveFieldNames($config->getFieldSet());
        $this->valuesFactory = new FieldValuesFactory(
            $this->errors,
            $this->validator,
            $this->config->getMaxValues()
        );
    }

    private function resolveFieldNames(FieldSet $fieldSet): array
    {
        $names = [];

        foreach ($fieldSet->all() as $name => $field) {
            $names[$name] = $name;
        }

        return $names;
    }
}
