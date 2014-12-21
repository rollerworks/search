<?php

/*
 * This file is part of the RollerworksSearch Component package.
 *
 * (c) 2012-2014 Sebastiaan Stok <s.stok@rollerscapes.net>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Rollerworks\Component\Search\Input;

use Rollerworks\Component\Search\Exception\GroupsNestingException;
use Rollerworks\Component\Search\Exception\GroupsOverflowException;
use Rollerworks\Component\Search\Exception\TransformationFailedException;
use Rollerworks\Component\Search\Exception\UnknownFieldException;
use Rollerworks\Component\Search\Exception\UnsupportedValueTypeException;
use Rollerworks\Component\Search\FieldAliasResolverInterface;
use Rollerworks\Component\Search\FieldConfigInterface;
use Rollerworks\Component\Search\FieldSet;
use Rollerworks\Component\Search\InputProcessorInterface;
use Rollerworks\Component\Search\Value\Range;
use Rollerworks\Component\Search\ValuesBag;
use Rollerworks\Component\Search\ValuesError;

/**
 * AbstractInput provides the shared logic for the InputProcessors.
 *
 * @author Sebastiaan Stok <s.stok@rollerscapes.net>
 */
abstract class AbstractInput implements InputProcessorInterface
{
    /**
     * @var FieldAliasResolverInterface|null
     */
    protected $aliasResolver;

    /**
     * @var ProcessorConfig
     */
    protected $config;

    /**
     * @param FieldAliasResolverInterface $aliasResolver
     */
    public function __construct(FieldAliasResolverInterface $aliasResolver)
    {
        $this->aliasResolver = $aliasResolver;
    }

    /**
     * Get 'real' fieldname.
     *
     * This will pass the Field through the alias resolver.
     *
     * @param string $name
     *
     * @return string
     *
     * @throws UnknownFieldException When there is no field found.
     * @throws \LogicException       When there is no FieldSet configured.
     */
    protected function getFieldName($name)
    {
        $fieldSet = $this->config->getFieldSet();
        $name = $this->aliasResolver->resolveFieldName($fieldSet, $name);

        if (!$fieldSet->has($name)) {
            throw new UnknownFieldException($name);
        }

        return $name;
    }

    /**
     * Checks if the maximum group nesting level is exceeded.
     *
     * @param int $groupIdx
     * @param int $nestingLevel
     *
     * @throws GroupsNestingException
     */
    protected function validateGroupNesting($groupIdx, $nestingLevel)
    {
        if ($nestingLevel > $this->config->getMaxNestingLevel()) {
            throw new GroupsNestingException(
                $this->config->getMaxNestingLevel(),
                $groupIdx,
                $nestingLevel
            );
        }
    }

    /**
     * Checks if the maximum group count is exceeded.
     *
     * @param int $groupIdx
     * @param int $count
     * @param int $nestingLevel
     *
     * @throws GroupsOverflowException
     */
    protected function validateGroupsCount($groupIdx, $count, $nestingLevel)
    {
        if ($count > $this->config->getMaxGroups()) {
            throw new GroupsOverflowException($this->config->getMaxGroups(), $count, $groupIdx, $nestingLevel);
        }
    }

    /**
     * @param Range                $range
     * @param FieldConfigInterface $fieldConfig
     * @param ValuesBag            $valuesBag
     * @param string               $path
     */
    protected function validateRangeBounds(
        Range $range,
        FieldConfigInterface $fieldConfig,
        ValuesBag $valuesBag,
        $path
    ) {
        if (!$fieldConfig->getValueComparison()->isLower(
            $range->getLower(),
            $range->getUpper(),
            $fieldConfig->getOptions()
        )) {
            $lowerValue = $range->getViewLower();
            $upperValue = $range->getViewUpper();

            $message = 'Lower range-value {{ lower }} should be lower then upper range-value {{ upper }}.';
            $params = array(
                '{{ lower }}' => strpos($lowerValue, ' ') ? "'".$lowerValue."'" : $lowerValue,
                '{{ upper }}' => strpos($upperValue, ' ') ? "'".$upperValue."'" : $upperValue,
            );

            $valuesBag->addError(
                new ValuesError($path, strtr($message, $params), $message, $params)
            );
        }
    }

    /**
     * Checks if the given field accepts the given value-type.
     *
     * @param FieldConfigInterface $fieldConfig
     * @param string               $type
     *
     * @throws UnsupportedValueTypeException
     */
    protected function assertAcceptsType(FieldConfigInterface $fieldConfig, $type)
    {
        switch ($type) {
            case 'range':
                if (!$fieldConfig->acceptRanges()) {
                    throw new UnsupportedValueTypeException($fieldConfig->getName(), $type);
                }
                break;

            case 'comparison':
                if (!$fieldConfig->acceptCompares()) {
                    throw new UnsupportedValueTypeException($fieldConfig->getName(), $type);
                }
                break;

            case 'pattern-match':
                if (!$fieldConfig->acceptPatternMatch()) {
                    throw new UnsupportedValueTypeException($fieldConfig->getName(), $type);
                }
                break;
        }
    }

    /**
     * Transforms the value if a value transformer is set.
     *
     * @param mixed                $value The value to transform
     * @param FieldConfigInterface $config
     * @param string               $path
     * @param ValuesBag            $valuesBag
     *
     * @return string|null Returns null when the value is empty or invalid
     */
    protected function normToView($value, FieldConfigInterface $config, $path, ValuesBag $valuesBag)
    {
        // Scalar values should be converted to strings to
        // facilitate differentiation between empty ("") and zero (0).
        if (!$config->getViewTransformers() || null === $value) {
            if (null !== $value && !is_scalar($value)) {
                throw new \RuntimeException(
                    sprintf(
                        'Norm value of type %s is not a scalar value or null and not cannot be '.
                        'converted to a string. You must set a viewTransformer for field "%s" with type "%s".',
                        gettype($value),
                        $config->getName(),
                        $config->getType()->getName()
                    )
                );
            }

            return (string) $value;
        }

        try {
            foreach ($config->getViewTransformers() as $transformer) {
                $value = $transformer->transform($value);
            }

            return $value;
        } catch (TransformationFailedException $e) {
            $valuesBag->addError(
                new ValuesError(
                    $path,
                    $config->getOption('invalid_message', $e->getMessage()),
                    $config->getOption('invalid_message', $e->getMessage()),
                    $config->getOption('invalid_message_parameters', array()),
                    null,
                    $e
                )
            );
        }

        return;
    }

    /**
     * Reverse transforms a value if a value transformer is set.
     *
     * @param string               $value  The value to reverse transform
     * @param FieldConfigInterface $config
     * @param string               $path
     * @param ValuesBag          $valuesBag
     *
     * @return mixed Returns null when the value is empty or invalid
     */
    protected function viewToNorm($value, FieldConfigInterface $config, $path, ValuesBag $valuesBag)
    {
        $transformers = $config->getViewTransformers();

        if (!$transformers) {
            return '' === $value ? null : $value;
        }

        try {
            for ($i = count($transformers) - 1; $i >= 0; --$i) {
                $value = $transformers[$i]->reverseTransform($value);
            }

            return $value;
        } catch (TransformationFailedException $e) {
            $valuesBag->addError(
                new ValuesError(
                    $path,
                    $config->getOption('invalid_message', $e->getMessage()),
                    $config->getOption('invalid_message', $e->getMessage()),
                    $config->getOption('invalid_message_parameters', array()),
                    null,
                    $e
                )
            );
        }

        return;
    }
}
