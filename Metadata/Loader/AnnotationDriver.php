<?php

/**
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

use Rollerworks\Bundle\RecordFilterBundle\Annotation\Field as AnnotationField;
use Rollerworks\Bundle\RecordFilterBundle\Annotation\Doctrine\SqlValueConversion;
use Rollerworks\Bundle\RecordFilterBundle\Annotation\Doctrine\SqlFieldConversion;
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

                    $propertyMetadata->filter_name = $annotation->getName();
                    $propertyMetadata->label       = $annotation->getLabel();
                    $propertyMetadata->required    = $annotation->isRequired();
                    $propertyMetadata->type        = new FilterTypeConfig($annotation->getType()->getName(), $annotation->getType()->getParams());

                    $propertyMetadata->acceptRanges   = $annotation->acceptsRanges();
                    $propertyMetadata->acceptCompares = $annotation->acceptsCompares();
                }

                if ($propertyMetadata && ($annotation instanceof SqlValueConversion || $annotation instanceof SqlFieldConversion)) {
                    $this->setDoctrineOrm($propertyMetadata, $annotation);
                }
            }

            if ($propertyMetadata) {
                $classMetadata->addPropertyMetadata($propertyMetadata);
            }
        }

        return $classMetadata;
    }

    /**
     * @param PropertyMetadata                      $propertyMetadata
     * @param SqlValueConversion|SqlFieldConversion $annotation
     */
    private function setDoctrineOrm(PropertyMetadata $propertyMetadata, $annotation)
    {
        if (!$propertyMetadata->getDoctrineConfig('orm')) {
            $propertyMetadata->setDoctrineConfig('orm', new OrmConfig());
        }

        if ($annotation instanceof SqlFieldConversion) {
            $propertyMetadata->getDoctrineConfig('orm')->setFieldConversion($annotation->getService(), $annotation->getParams());
        } else {
            $propertyMetadata->getDoctrineConfig('orm')->setValueConversion($annotation->getService(), $annotation->getParams());
        }
    }
}
