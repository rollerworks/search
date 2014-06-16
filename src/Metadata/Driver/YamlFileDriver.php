<?php

/**
 * This file is part of the RollerworksSearch Component package.
 *
 * (c) 2012-2014 Sebastiaan Stok <s.stok@rollerscapes.net>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Rollerworks\Component\Search\Metadata\Driver;

use Metadata\MergeableClassMetadata;
use Rollerworks\Component\Search\Exception\InvalidArgumentException;
use Rollerworks\Component\Search\Metadata\PropertyMetadata;
use Symfony\Component\Yaml\Yaml;

/**
 * YamlFileDriver.
 *
 * @author Sebastiaan Stok <s.stok@rollerscapes.net>
 */
class YamlFileDriver extends AbstractFileDriver
{
    /**
     * {@inheritdoc}
     */
    protected function loadMetadataFromFile(\ReflectionClass $class, $file, $noReflection = false)
    {
        $classMetadata = new MergeableClassMetadata($class->name);
        $data = Yaml::parse($file);

        foreach ($data as $propertyName => $property) {
            if (!isset($property['name'])) {
                throw new InvalidArgumentException(
                    sprintf(
                        'No "name" found in property metadata of class "%s" property "%s".',
                        $class->name,
                        $propertyName
                    )
                );
            }

            if (!isset($property['type'])) {
                throw new InvalidArgumentException(
                    sprintf(
                        'No "type" found in property metadata of class "%s" property "%s".',
                        $class->name,
                        $propertyName
                    )
                );
            }

            $propertyMetadata = new PropertyMetadata($class->name, $propertyName);
            $propertyMetadata->fieldName = $property['name'];
            $propertyMetadata->required = (isset($property['required']) ? $property['required'] : false);
            $propertyMetadata->type = $property['type'];

            if (isset($property['options'])) {
                $propertyMetadata->options = $property['options'];
            }

            if ($noReflection) {
                $propertyMetadata->reflection = null;
            }

            $classMetadata->addPropertyMetadata($propertyMetadata);
        }

        return $classMetadata;
    }

    /**
     * Returns the extension of the file.
     *
     * @return string
     */
    protected function getExtension()
    {
        return 'yml';
    }
}
