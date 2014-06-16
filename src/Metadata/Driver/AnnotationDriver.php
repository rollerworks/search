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

use Doctrine\Common\Annotations\Reader;
use Metadata\Driver\DriverInterface;
use Metadata\MergeableClassMetadata;
use Rollerworks\Component\Search\Metadata\Field;
use Rollerworks\Component\Search\Metadata\PropertyMetadata;

/**
 * AnnotationDriver.
 *
 * @author Sebastiaan Stok <s.stok@rollerscapes.net>
 */
class AnnotationDriver implements DriverInterface
{
    /**
     * @var Reader
     */
    private $reader;

    /**
     * Constructor.
     *
     * @param Reader $reader
     */
    public function __construct(Reader $reader)
    {
        $this->reader = $reader;
    }

    /**
     * @param \ReflectionClass $class
     * @param bool             $noReflection Don't use this parameter, its only used for testing
     *
     * @return MergeableClassMetadata|null
     */
    public function loadMetadataForClass(\ReflectionClass $class, $noReflection = false)
    {
        $classMetadata = new MergeableClassMetadata($class->name);

        if ($noReflection) {
            $classMetadata->reflection = null;
            $classMetadata->createdAt = null;
        }

        $hasMetadata = false;

        foreach ($class->getProperties() as $reflectionProperty) {
            $annotation = $this->reader->getPropertyAnnotation(
                $reflectionProperty,
                'Rollerworks\Component\Search\Metadata\Field'
            );

            if (null !== $annotation) {
                /** @var Field $annotation */
                $propertyMetadata = new PropertyMetadata($class->name, $reflectionProperty->name);
                $propertyMetadata->fieldName = $annotation->getName();
                $propertyMetadata->required = $annotation->isRequired();
                $propertyMetadata->type = $annotation->getType();
                $propertyMetadata->options = $annotation->getOptions();

                if ($noReflection) {
                    $propertyMetadata->reflection = null;
                }

                $classMetadata->addPropertyMetadata($propertyMetadata);
                $hasMetadata = true;
            }
        }

        if (!$hasMetadata) {
            return null;
        }

        return $classMetadata;
    }
}
