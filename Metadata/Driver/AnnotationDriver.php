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
use Rollerworks\RecordFilterBundle\Annotation\Field as AnnotationField;
use Rollerworks\RecordFilterBundle\Annotation\SqlConversion;

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
     *
     * @return MergeableClassMetadata
     */
    public function loadMetadataForClass(\ReflectionClass $class)
    {
        $classMetadata = new MergeableClassMetadata($class->getName());

        foreach ($class->getProperties() as $reflectionProperty) {
            $propertyMetadata = new PropertyMetadata($class->getName(), $reflectionProperty->getName());

            foreach ($this->reader->getPropertyAnnotations($reflectionProperty) as $annotation) {

                /** @var \Rollerworks\RecordFilterBundle\Annotation\Field $annotation */
                if ($annotation instanceof AnnotationField) {
                    $propertyMetadata->filter_name = $annotation->getName();
                    $propertyMetadata->required    = $annotation->isRequired();

                    $propertyMetadata->type   = self::getRealType($annotation->getType());
                    $propertyMetadata->params = $annotation->getParams();

                    $propertyMetadata->acceptRanges   = $annotation->acceptsRanges();
                    $propertyMetadata->acceptCompares = $annotation->acceptsCompares();
                }
                /** @var \Rollerworks\RecordFilterBundle\Annotation\SqlConversion $annotation */
                elseif ($annotation instanceof SqlConversion) {
                    $propertyMetadata->setSqlConversion($annotation->getClass(), $annotation->getParams());
                }
            }

            $classMetadata->addPropertyMetadata($propertyMetadata);
        }

        return $classMetadata;
    }
}
