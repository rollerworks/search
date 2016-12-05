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

/**
 * @author Sebastiaan Stok <s.stok@rollerscapes.net>
 */
class SearchFactory implements SearchFactoryInterface
{
    private $registry;
    private $fieldSetRegistry;
    private $serializer;

    public function __construct(FieldRegistryInterface $registry, FieldSetRegistryInterface $fieldSetRegistry)
    {
        $this->registry = $registry;
        $this->fieldSetRegistry = $fieldSetRegistry;
        $this->serializer = new SearchConditionSerializer($this);
    }

    /**
     * {@inheritdoc}
     */
    public function createFieldSet($configurator)
    {
        if (!$configurator instanceof FieldSetConfiguratorInterface) {
            $configurator = $this->fieldSetRegistry->getConfigurator($configurator);
        }

        $builder = $this->createFieldSetBuilder();
        $configurator->buildFieldSet($builder);

        return $builder->getFieldSet(get_class($configurator));
    }

    /**
     * {@inheritdoc}
     */
    public function createField($name, string $type, array $options = [])
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
    public function createFieldSetBuilder()
    {
        return new FieldSetBuilder($this);
    }

    /**
     * {@inheritdoc}
     */
    public function getSerializer(): SearchConditionSerializer
    {
        return $this->serializer;
    }
}
