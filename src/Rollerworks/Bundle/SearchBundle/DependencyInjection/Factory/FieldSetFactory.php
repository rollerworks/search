<?php

/*
 * This file is part of the RollerworksSearchBundle package.
 *
 * (c) Sebastiaan Stok <s.stok@rollerscapes.net>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Rollerworks\Bundle\SearchBundle\DependencyInjection\Factory;

use Metadata\MetadataFactoryInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;

/**
 * FieldSetFactory, provides registering FieldSets as services.
 *
 * @author Sebastiaan Stok <s.stok@rollerscapes.net>
 */
class FieldSetFactory
{
    /**
     * @var ContainerBuilder
     */
    private $container;

    /**
     * @var MetadataFactoryInterface
     */
    private $metadataFactory;

    /**
     * @param ContainerBuilder         $container
     * @param MetadataFactoryInterface $metadataFactory
     */
    public function __construct(ContainerBuilder $container, MetadataFactoryInterface $metadataFactory = null)
    {
        $this->container = $container;
        $this->metadataFactory = $metadataFactory;
    }

    /**
     * @param $name
     *
     * @return FieldSetBuilder
     */
    public function createFieldSetBuilder($name)
    {
        return new FieldSetBuilder($name, $this->metadataFactory);
    }

    /**
     * Registers the fieldset in the Service container.
     *
     * The FieldSet is registered as 'rollerworks_search.fieldset.[FieldSetName]'.
     *
     * @param FieldSet $fieldSet
     */
    public function register(FieldSet $fieldSet)
    {
        $fieldSetDef = new Definition('Rollerworks\Component\Search\FieldSet');
        $fieldSetDef->addArgument($fieldSet->getName());

        foreach ($fieldSet->all() as $name => $field) {
            $fieldDef = new Definition();
            $fieldDef->setFactoryService('rollerworks_search.factory');

            if (!empty($field['model_class'])) {
                $fieldDef->setFactoryMethod('createFieldForProperty');
                $fieldDef->addArgument($field['model_class']);
                $fieldDef->addArgument($field['model_property']);
            } else {
                $fieldDef->setFactoryMethod('createField');
            }

            $fieldDef->addArgument($field['type']);
            $fieldDef->addArgument($field['options']);
            $fieldDef->addArgument($field['required']);

            $fieldSetDef->addMethodCall('set', array($name, $fieldDef));
        }

        $this->container->setDefinition(sprintf('rollerworks_search.fieldset.%s', $fieldSet->getName()), $fieldSetDef);
    }
}
