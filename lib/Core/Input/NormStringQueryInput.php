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

/**
 * Processes input in the StringInput syntax using the Normalized value format.
 */
final class NormStringQueryInput extends StringInput
{
    public const FIELD_LEXER_OPTION_NAME = 'norm_string_query.value_lexer';
    public const VALUE_EXPORTER_OPTION_NAME = 'norm_string_query.value_exporter';

    protected function initForProcess(ProcessorConfig $config): void
    {
        $names = [];
        $fieldSet = $config->getFieldSet();

        foreach ($fieldSet->all() as $name => $field) {
            if ($fieldSet->isPrivate($name)) {
                continue;
            }

            $names[$name] = $name;

            if (null !== $customerMatcher = $field->getOption(self::FIELD_LEXER_OPTION_NAME)) {
                $this->valueLexers[$name] = $customerMatcher;
            }
        }

        $this->fields = $names;
        $this->structureBuilder = new ConditionStructureBuilder($this->config, $this->validator, $this->errors);
        $this->orderStructureBuilder = new ConditionStructureBuilder($this->config, $this->validator, $this->errors);
    }
}
