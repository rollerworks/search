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
 * StringQueryInput - processes input in the StringInput syntax
 * using the View value format.
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
     * Constructor.
     *
     * @param Validator|null $validator
     * @param callable|null  $labelResolver A callable to resolve the actual label
     *                                      of the field, receives a
     *                                      FieldConfigInterface instance.
     *                                      If null the `label` option value is
     *                                      used instead
     */
    public function __construct(Validator $validator = null, callable $labelResolver = null)
    {
        parent::__construct($validator);
        $this->labelResolver = $labelResolver ?? function (FieldConfig $field) {
            return $field->getOption('label', $field->getName());
        };
    }

    protected function initForProcess(ProcessorConfig $config): void
    {
        $labels = [];
        $callable = $this->labelResolver;

        foreach ($config->getFieldSet()->all() as $name => $field) {
            $label = $callable($field);
            $labels[$label] = $name;

            if (null !== $customerMatcher = $field->getOption(self::FIELD_LEXER_OPTION_NAME)) {
                $this->valueLexers[$name] = $customerMatcher;
            }
        }

        $this->fields = $labels;
        $this->valuesFactory = new FieldValuesByViewFactory(
            $this->errors,
            $this->validator,
            $this->config->getMaxValues()
        );
    }
}
