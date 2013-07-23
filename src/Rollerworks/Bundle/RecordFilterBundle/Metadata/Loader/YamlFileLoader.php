<?php

/*
 * This file is part of the RollerworksRecordFilterBundle.
 *
 * (c) Sebastiaan Stok <s.stok@rollerscapes.net>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Rollerworks\Bundle\RecordFilterBundle\Metadata\Loader;

use Rollerworks\Bundle\RecordFilterBundle\Metadata\PropertyMetadata;
use Rollerworks\Bundle\RecordFilterBundle\Metadata\FilterTypeConfig;
use Rollerworks\Bundle\RecordFilterBundle\Metadata\Doctrine\OrmConfig;
use Rollerworks\Bundle\RecordFilterBundle\Exception\MetadataException;
use Metadata\Driver\AbstractFileDriver;
use Metadata\MergeableClassMetadata;
use Symfony\Component\Yaml\Yaml;

/**
 * YamlFileLoader.
 *
 * @author Sebastiaan Stok <s.stok@rollerscapes.net>
 */
class YamlFileLoader extends AbstractFileDriver
{
    /**
     * {@inheritDoc}
     */
    protected function loadMetadataFromFile(\ReflectionClass $class, $file)
    {
        $classMetadata = new MergeableClassMetadata($class->name);
        $data = Yaml::parse($file);

        foreach ($data as $propertyName => $property) {
            $propertyMetadata = new PropertyMetadata($class->name, $propertyName);

            if (!isset($property['name'])) {
                throw new MetadataException(sprintf('No name found in property metadata of class "%s" property "%s".', $class->name, $propertyName));
            }

            $propertyMetadata->filter_name = $property['name'];
            $propertyMetadata->label       = (isset($property['label']) ? $property['label'] : null);
            $propertyMetadata->required    = (isset($property['required']) ? $property['required'] : false);

            if (isset($property['type'])) {
                if (is_string($property['type'])) {
                    $propertyMetadata->type = new FilterTypeConfig($property['type']);
                } else {
                    if (!isset($property['type']['name'])) {
                        throw new MetadataException(sprintf('Type of "%s" must be either a string/null or set at type[name] in property metadata of class "%s" property "%s".', $property['name'], $class->name, $propertyName));
                    }

                    if (isset($property['type']['params']) && !is_array($property['type']['params'])) {
                        throw new MetadataException(sprintf('Type parameters of "%s" must be either a null or set as array at type[params] in property metadata of class "%s" property "%s".', $property['name'], $class->name, $propertyName));
                    }

                    $propertyMetadata->type = new FilterTypeConfig($property['type']['name'], (isset($property['type']['params']) ? $property['type']['params'] : array()));
                }
            } else {
                $propertyMetadata->type = new FilterTypeConfig(null);
            }

            $propertyMetadata->acceptRanges   = (isset($property['accept-ranges']) ? $property['accept-ranges'] : false);
            $propertyMetadata->acceptCompares = (isset($property['accept-compares']) ? $property['accept-compares'] : false);

            if (isset($property['doctrine']['orm'])) {
                $this->setDoctrineOrm($propertyMetadata, $property['doctrine']['orm'], $class->name, $propertyName);
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

    /**
     * @param PropertyMetadata $propertyMetadata
     * @param array            $property
     * @param string           $classRef
     * @param string           $propertyRef
     */
    protected function setDoctrineOrm(PropertyMetadata $propertyMetadata, array $property, $classRef, $propertyRef)
    {
        $propertyMetadata->setDoctrineConfig('orm', new OrmConfig());

        if (isset($property['field-conversion'])) {
            if (isset($property['field-conversion'])) {
                $this->validateConversion($property['field-conversion'], 'field-conversion', $classRef, $propertyRef);
            }

            if (is_string($property['field-conversion'])) {
                $propertyMetadata->getDoctrineConfig('orm')->setFieldConversion($property['field-conversion']);
            } else {
                $propertyMetadata->getDoctrineConfig('orm')->setFieldConversion(
                    $property['field-conversion']['service'],
                    (isset($property['field-conversion']['params']) ? $property['field-conversion']['params'] : array())
                );
            }
        }

        if (isset($property['value-conversion'])) {
            if (isset($property['value-conversion'])) {
                $this->validateConversion($property['value-conversion'], 'value-conversion', $classRef, $propertyRef);
            }

            if (is_string($property['value-conversion'])) {
                $propertyMetadata->getDoctrineConfig('orm')->setValueConversion($property['value-conversion']);
            } else {
                $propertyMetadata->getDoctrineConfig('orm')->setValueConversion(
                    $property['field-conversion']['service'],
                    (isset($property['value-conversion']['params']) ? $property['value-conversion']['params'] : array())
                );
            }
        }
    }

    /**
     * @param array  $property
     * @param string $type
     * @param string $classRef
     * @param string $propertyRef
     *
     * @throws MetadataException when missing required information
     */
    private function validateConversion($property, $type, $classRef, $propertyRef)
    {
        if (is_string($property)) {
            return;
        }

        if (is_array($property) && !isset($property['service']) ) {
            throw new MetadataException(sprintf('Missing %s["service"] key in property "%s" of class "%s".', $type, $classRef, $propertyRef));
        }
    }
}
