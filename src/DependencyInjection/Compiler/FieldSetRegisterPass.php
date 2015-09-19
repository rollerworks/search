<?php

/*
 * This file is part of the RollerworksSearch package.
 *
 * (c) Sebastiaan Stok <s.stok@rollerscapes.net>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Rollerworks\Bundle\SearchBundle\DependencyInjection\Compiler;

use Rollerworks\Component\Search\Extension\Symfony\DependencyInjection\Factory\FieldSetFactory;
use Symfony\Component\Config\Resource\FileResource;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

final class FieldSetRegisterPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container)
    {
        if (!$container->hasParameter('rollerworks_search.fieldsets_configuration')) {
            return;
        }

        $fieldSets = $container->getParameter('rollerworks_search.fieldsets_configuration');

        $factory = new FieldSetFactory(
            $container,
            $container->get('rollerworks_search.metadata_factory', ContainerBuilder::NULL_ON_INVALID_REFERENCE)
        );

        foreach ($fieldSets as $name => $fieldSetConfig) {
            $fieldSet = $factory->createFieldSetBuilder($name);

            foreach ($fieldSetConfig['imports'] as $import) {
                $fieldSet->importFromClass(
                    $import['class'],
                    $import['include_fields'],
                    $import['exclude_fields']
                );

                $r = new \ReflectionClass($import['class']);
                $container->addResource(new FileResource($r->getFileName()));
            }

            foreach ($fieldSetConfig['fields'] as $fieldName => $field) {
                $fieldSet->set(
                    $fieldName,
                    $field['type'],
                    $field['options'],
                    $field['required'],
                    $field['model_class'],
                    $field['model_property']
                );
            }

            $factory->register($fieldSet->getFieldSet());
        }
    }
}
