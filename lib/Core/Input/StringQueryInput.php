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

use Rollerworks\Component\Search\Field\FieldConfig;

/**
 * Processes input in the StringInput syntax using the View value format.
 */
final class StringQueryInput extends StringInput
{
    public const FIELD_LEXER_OPTION_NAME = 'string_query.value_lexer';
    public const VALUE_EXPORTER_OPTION_NAME = 'string_query.value_export';

    /**
     * @var callable
     */
    private $labelResolver;

    /**
     * @param callable|null $labelResolver a callable to resolve the actual label
     *                                     of the field, receives a
     *                                     FieldConfigInterface instance.
     *                                     If null the `label` option value is
     *                                     used instead
     */
    public function __construct(?Validator $validator = null, ?callable $labelResolver = null)
    {
        parent::__construct($validator);
        $this->labelResolver = $labelResolver ?? static fn (FieldConfig $field) => $field->getOption('label', $field->getName());
    }

    protected function initForProcess(ProcessorConfig $config): void
    {
        $labels = [];
        $callable = $this->labelResolver;
        $fieldSet = $config->getFieldSet();

        foreach ($fieldSet->all() as $name => $field) {
            if ($fieldSet->isPrivate($name)) {
                continue;
            }

            $label = $callable($field);
            $labels[$label] = $name;

            $customerMatcher = $field->getOption(self::FIELD_LEXER_OPTION_NAME);

            if ($customerMatcher !== null) {
                $this->valueLexers[$name] = $customerMatcher;
            }
        }

        $this->fields = $labels;
        $this->structureBuilder = new ConditionStructureByViewBuilder(
            $this->config,
            $this->validator,
            $this->errors
        );
        $this->orderStructureBuilder = new OrderStructureBuilder(
            $this->config,
            $this->validator,
            $this->errors,
            '',
            true
        );
    }
}
