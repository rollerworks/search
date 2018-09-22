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
use Rollerworks\Component\Search\Field\TypeRegistry;

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

    /**
     * {@inheritdoc}
     */
    public function createFieldSet($configurator): FieldSet
    {
        if (!$configurator instanceof FieldSetConfigurator) {
            $configurator = $this->fieldSetRegistry->getConfigurator($configurator);
        }

        $builder = $this->createFieldSetBuilder();
        $configurator->buildFieldSet($builder);

        return $builder->getFieldSet(\get_class($configurator));
    }

    /**
     * {@inheritdoc}
     */
    public function createField(string $name, string $type, array $options = []): FieldConfig
    {
        $type = $this->registry->getType($type);
        $field = $type->createField($name, $options);

        // Explicitly call buildType() in order to be able to override either
        // createField() or buildType() in the resolved field type
        $type->buildType($field, $field->getOptions());

        return $field;
    }

    /**
     * {@inheritdoc}
     */
    public function createFieldSetBuilder(): FieldSetBuilder
    {
        return new GenericFieldSetBuilder($this);
    }

    /**
     * {@inheritdoc}
     */
    public function getSerializer(): SearchConditionSerializer
    {
        return $this->serializer;
    }
}
