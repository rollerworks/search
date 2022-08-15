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

use Rollerworks\Component\Search\ConditionErrorMessage;
use Rollerworks\Component\Search\DataTransformer;
use Rollerworks\Component\Search\ErrorList;
use Rollerworks\Component\Search\Exception\InvalidArgumentException;
use Rollerworks\Component\Search\Exception\TransformationFailedException;
use Rollerworks\Component\Search\Exception\UnexpectedTypeException;
use Rollerworks\Component\Search\Exception\ValuesOverflowException;
use Rollerworks\Component\Search\Field\FieldConfig;
use Rollerworks\Component\Search\FieldSet;
use Rollerworks\Component\Search\StructureBuilder;
use Rollerworks\Component\Search\Value\ValuesBag;
use Rollerworks\Component\Search\Value\ValuesGroup;

/**
 * @author Dalibor Karlović <dalibor@flexolabs.io>
 */
final class OrderStructureBuilder implements StructureBuilder
{
    /** @var FieldSet */
    private $fieldSet;

    /** @var ErrorList */
    private $errorList;

    /** @var Validator */
    private $validator;

    /** @var ValuesGroup */
    private $valuesGroup;

    /** @var ValuesBag|null */
    private $valuesBag;

    /** @var FieldConfig|null */
    private $fieldConfig;

    /** @var string */
    private $path;

    /**
     * False when not set, null when undetected (lazy loaded).
     *
     * @var bool|DataTransformer|null
     */
    private $inputTransformer;

    public function __construct(ProcessorConfig $config, Validator $validator, ErrorList $errorList, string $path = '')
    {
        $this->fieldSet = $config->getFieldSet();
        $this->validator = $validator;
        $this->path = $path ?: 'order';
        $this->errorList = $errorList ?? new ErrorList();
        $this->valuesGroup = new ValuesGroup();
    }

    public function getErrors(): ErrorList
    {
        return $this->errorList;
    }

    public function getCurrentPath(): string
    {
        return $this->path;
    }

    public function getRootGroup(): ValuesGroup
    {
        return $this->valuesGroup;
    }

    public function enterGroup(string $groupLocal = 'AND', string $path = '[%d]'): void
    {
        throw new InvalidArgumentException('Order clauses do not support nesting');
    }

    public function leaveGroup(): void
    {
        throw new InvalidArgumentException('Order clauses do not support nesting');
    }

    public function field(string $name, string $path): void
    {
        if (! $this->valuesGroup->hasField($name)) {
            $this->valuesGroup->addField($name, new ValuesBag());
        }

        $this->fieldConfig = $this->fieldSet->get($name);

        $this->valuesBag = $this->valuesGroup->getField($name);

        $this->validator->initializeContext($this->fieldConfig, $this->errorList);
    }

    public function simpleValue($value, string $path): void
    {
        if ($this->valuesBag === null) {
            throw new \LogicException('Cannot add value to unknown bag');
        }

        if ($this->valuesBag->count()) {
            throw new ValuesOverflowException($this->fieldConfig->getName(), 1, $path);
        }

        if (($modelVal = $this->inputToNorm($value, $path)) !== null) {
            $this->validator->validate($modelVal, 'simple', $value, $path);
        }

        $this->valuesBag->addSimpleValue($modelVal);
    }

    public function excludedSimpleValue($value, string $path): void
    {
        throw new UnexpectedTypeException($this->fieldConfig->getName(), $path);
    }

    /**
     * @param array $path [path, lower-path-pattern, upper-path-pattern]
     */
    public function rangeValue($lower, $upper, bool $lowerInclusive, bool $upperInclusive, array $path): void
    {
        throw new UnexpectedTypeException($this->fieldConfig->getName(), $path);
    }

    /**
     * @param array $path [path, lower-path-pattern, upper-path-pattern]
     */
    public function excludedRangeValue($lower, $upper, bool $lowerInclusive, bool $upperInclusive, array $path): void
    {
        throw new UnexpectedTypeException($this->fieldConfig->getName(), $path);
    }

    /**
     * @param string $operator
     * @param array  $path     [base-path, operator-path, value-path]
     */
    public function comparisonValue($operator, $value, array $path): void
    {
        throw new UnexpectedTypeException($this->fieldConfig->getName(), $path);
    }

    /**
     * @param string $type
     * @param string $value
     * @param array  $path  [base-path, value-path, type-path]
     */
    public function patterMatchValue($type, $value, bool $caseInsensitive, array $path): void
    {
        throw new UnexpectedTypeException($this->fieldConfig->getName(), $path);
    }

    public function endValues(): void
    {
        $this->fieldConfig = null;
        $this->valuesBag = null;
    }

    private function addError(ConditionErrorMessage $error): void
    {
        $this->errorList[] = $error;
    }

    /**
     * Reverse transforms a value if a value transformer is set.
     *
     * @return mixed returns null when the value is empty or invalid.
     *               Note: When the value is invalid an error is registered
     */
    private function inputToNorm($value, string $path)
    {
        if ($this->inputTransformer === null) {
            $this->inputTransformer = $this->fieldConfig->getNormTransformer() ?? false;
        }

        if ($this->inputTransformer === false) {
            if ($value !== null && ! \is_scalar($value)) {
                $e = new \RuntimeException(
                    sprintf(
                        'Norm value of type %s is not a scalar value or null and not cannot be ' .
                        'converted to a string. You must set a NormTransformer for field "%s" with type "%s".',
                        \gettype($value),
                        $this->fieldConfig->getName(),
                        \get_class($this->fieldConfig->getType()->getInnerType())
                    )
                );

                $error = new ConditionErrorMessage(
                    $path,
                    $this->fieldConfig->getOption('invalid_message', $e->getMessage()),
                    $this->fieldConfig->getOption('invalid_message', $e->getMessage()),
                    $this->fieldConfig->getOption('invalid_message_parameters', []),
                    null,
                    $e
                );

                $this->addError($error);

                return null;
            }

            return $value === '' ? null : $value;
        }

        try {
            return $this->inputTransformer->reverseTransform($value);
        } catch (TransformationFailedException $e) {
            $error = new ConditionErrorMessage(
                $path,
                $this->fieldConfig->getOption('invalid_message', $e->getMessage()),
                $this->fieldConfig->getOption('invalid_message', $e->getMessage()),
                $this->fieldConfig->getOption('invalid_message_parameters', []),
                null,
                $e
            );

            $this->addError($error);

            return null;
        }
    }
}
