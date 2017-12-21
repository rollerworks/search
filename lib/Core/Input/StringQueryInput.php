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
use Rollerworks\Component\Search\FieldSet;

/**
 * StringQueryInput - processes input in the StringInput syntax
 * using the View value format.
 */
final class StringQueryInput extends StringInput
{
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
        $this->fields = $this->resolveLabels($config->getFieldSet());
        $this->valuesFactory = new FieldValuesByViewFactory(
            $this->errors,
            $this->validator,
            $this->config->getMaxValues()
        );
    }

    private function resolveLabels(FieldSet $fieldSet): array
    {
        $labels = [];
        $callable = $this->labelResolver;

        foreach ($fieldSet->all() as $name => $field) {
            $label = $callable($field);
            $labels[$label] = $name;
        }

        return $labels;
    }
}
