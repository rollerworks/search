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

use Rollerworks\Component\Search\FieldSet;

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
