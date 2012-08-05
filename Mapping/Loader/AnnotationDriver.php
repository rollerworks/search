<?php

/**
 * This file is part of the RollerworksRecordFilterBundle.
 *
 * (c) Sebastiaan Stok <s.stok@rollerscapes.net>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Rollerworks\Bundle\RecordFilterBundle\Mapping\Loader;

use Rollerworks\Bundle\RecordFilterBundle\Mapping\PropertyMetadata;
use Rollerworks\Bundle\RecordFilterBundle\Mapping\FilterTypeConfig;
use Rollerworks\Bundle\RecordFilterBundle\Annotation\Field as AnnotationField;
use Rollerworks\Bundle\RecordFilterBundle\Annotation\SqlConversion;
use Doctrine\Common\Annotations\Reader;
use Metadata\Driver\DriverInterface;
use Metadata\MergeableClassMetadata;

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
     *
     * @return MergeableClassMetadata
     */
    public function loadMetadataForClass(\ReflectionClass $class)
    {
        $classMetadata = new MergeableClassMetadata($class->getName());

        foreach ($class->getProperties() as $reflectionProperty) {
            $propertyMetadata = null;

            foreach ($this->reader->getPropertyAnnotations($reflectionProperty) as $annotation) {
                if ($annotation instanceof AnnotationField) {
                    $propertyMetadata = new PropertyMetadata($class->name, $reflectionProperty->name);

                    /** @var \Rollerworks\Bundle\RecordFilterBundle\Annotation\Field $annotation */
                    $propertyMetadata->filter_name = $annotation->getName();
                    $propertyMetadata->required    = $annotation->isRequired();
                    $propertyMetadata->type        = new FilterTypeConfig($annotation->getType()->getName(), $annotation->getType()->getParams());

                    $propertyMetadata->acceptRanges   = $annotation->acceptsRanges();
                    $propertyMetadata->acceptCompares = $annotation->acceptsCompares();
                } elseif ($propertyMetadata && $annotation instanceof SqlConversion) {
                    /** @var \Rollerworks\Bundle\RecordFilterBundle\Annotation\SqlConversion $annotation */
                    $propertyMetadata->setSqlConversion($annotation->getService(), $annotation->getParams());
                }
            }

            if ($propertyMetadata) {
                $classMetadata->addPropertyMetadata($propertyMetadata);
            }
        }

        return $classMetadata;
    }
}
