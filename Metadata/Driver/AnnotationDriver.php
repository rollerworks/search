<?php

/**
 * This file is part of the RollerworksRecordFilterBundle.
 *
 * (c) Sebastiaan Stok <s.stok@rollerscapes.net>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Rollerworks\RecordFilterBundle\Metadata\Driver;

use Rollerworks\RecordFilterBundle\Metadata\PropertyMetadata;
use Metadata\Driver\DriverInterface;
use Metadata\MergeableClassMetadata;
use Doctrine\Common\Annotations\Reader;

/**
 * AnnotationDriver.
 *
 * @author Sebastiaan Stok <s.stok@rollerscapes.net>
 */
class AnnotationDriver extends AbstractDriver
{
    /**
     * @var Reader
     */
    private $reader;

    /**
     * Construct
     *
     * @param Reader $reader
     */
    public function __construct(Reader $reader)
    {
        $this->reader = $reader;
    }

    /**
     * @param \ReflectionClass $class
     * @return MergeableClassMetadata
     */
    public function loadMetadataForClass(\ReflectionClass $class)
    {
        $classMetadata = new MergeableClassMetadata($class->getName());

        foreach ($class->getProperties() as $reflectionProperty) {
            $propertyMetadata = new PropertyMetadata($class->getName(), $reflectionProperty->getName());

            $annotation = $this->reader->getPropertyAnnotation($reflectionProperty, 'Rollerworks\\RecordFilterBundle\\Annotation\\Field');

            /** @var \Rollerworks\RecordFilterBundle\Annotation\Field $annotation */
            if (null !== $annotation) {
                $propertyMetadata->name           = $annotation->getName();
                $propertyMetadata->required       = $annotation->isRequired();
                $propertyMetadata->type           = $this->getRealType($annotation->getType());
                $propertyMetadata->acceptRanges   = $annotation->acceptsRanges();
                $propertyMetadata->acceptCompares = $annotation->acceptsCompares();
                $propertyMetadata->params         = $annotation->getParams();
                $propertyMetadata->widgetsConfig  = $annotation->getWidget();
            }

            $classMetadata->addPropertyMetadata($propertyMetadata);
        }

        return $classMetadata;
    }
}