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

namespace Rollerworks\Component\Search;

use Rollerworks\Component\Search\Field\FieldConfig;
use Rollerworks\Component\Search\Field\OrderField;
use Rollerworks\Component\Search\Field\TypeRegistry;
use Symfony\Component\OptionsResolver\Exception\ExceptionInterface;

/**
 * @author Sebastiaan Stok <s.stok@rollerscapes.net>
 */
final class GenericSearchFactory implements SearchFactory
{
    private $registry;
    private $fieldSetRegistry;
    private $serializer;

    public function __construct(TypeRegistry $registry, FieldSetRegistry $fieldSetRegistry)
    {
        $this->registry = $registry;
        $this->fieldSetRegistry = $fieldSetRegistry;
        $this->serializer = new SearchConditionSerializer($this);
    }

    public function createFieldSet($configurator): FieldSet
    {
        if (! $configurator instanceof FieldSetConfigurator) {
            $configurator = $this->fieldSetRegistry->getConfigurator($configurator);
        }

        $builder = $this->createFieldSetBuilder();
        $configurator->buildFieldSet($builder);

        return $builder->getFieldSet($configurator::class);
    }

    public function createField(string $name, string $type, array $options = []): FieldConfig
    {
        if (OrderField::isOrder($name) && isset($options['type'])) {
            $options = $this->createOptionsForOrderField($name, $options);
        }

        $resolvedType = $this->registry->getType($type);
        $field = $resolvedType->createField($name, $options);

        // Explicitly call buildType() in order to be able to override either
        // createField() or buildType() in the resolved field type
        $resolvedType->buildType($field, $field->getOptions());

        return $field;
    }

    private function createOptionsForOrderField(string $name, array $options): array
    {
        $type = $this->registry->getType($options['type']);

        try {
            $options['type_options'] = $type->getOptionsResolver()->resolve($options['type_options'] ?? []);
        } catch (ExceptionInterface $e) {
            throw new $e(\sprintf('An error has occurred resolving the type-options of the order-field "%s"": ', $name) . $e->getMessage(), $e->getCode(), $e);
        }

        return $options;
    }

    public function createFieldSetBuilder(): FieldSetBuilder
    {
        return new GenericFieldSetBuilder($this);
    }

    public function getSerializer(): SearchConditionSerializer
    {
        return $this->serializer;
    }
}
