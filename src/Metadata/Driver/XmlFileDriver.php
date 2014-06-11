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
use Rollerworks\Component\Search\Metadata\SimpleXMLElement;
use Rollerworks\Component\Search\Util\XmlUtils;

/**
 * XmlFileDriver.
 *
 * @author Sebastiaan Stok <s.stok@rollerscapes.net>
 */
class XmlFileDriver extends AbstractFileDriver
{
    /**
     * {@inheritDoc}
     */
    protected function loadMetadataFromFile(\ReflectionClass $class, $file, $test = false)
    {
        $xml = $this->parseFile($file);
        $classMetadata = new MergeableClassMetadata($class->name);

        foreach ($xml as $property) {
            $propertyMetadata = $this->parseProperty($class, $property, $test);
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
        return 'xml';
    }

    /**
     * @param \ReflectionClass $class
     * @param SimpleXMLElement $property
     * @param boolean          $test
     *
     * @return PropertyMetadata
     */
    private function parseProperty(\ReflectionClass $class, SimpleXMLElement $property, $test)
    {
        $propertyMetadata = new PropertyMetadata($class->name, (string) $property['id']);

        $propertyMetadata->fieldName = (string) $property['name'];
        $propertyMetadata->required = (isset($property['required']) ? XmlUtils::phpize($property['required']) : false);
        $propertyMetadata->type = (string) $property['type'];

        if (isset($property->option)) {
            $propertyMetadata->options = $property->getArgumentsAsPhp('option');
        }

        if ($test) {
            $propertyMetadata->reflection = null;
        }

        return $propertyMetadata;
    }

    /**
     * Parses a XML file.
     *
     * @param string $file Path to a file
     *
     * @return SimpleXMLElement
     *
     * @throws InvalidArgumentException When loading of XML file returns error
     */
    private function parseFile($file)
    {
        try {
            $dom = XmlUtils::loadFile($file, __DIR__ . '/schema/dic/metadata/metadata-1.0.xsd');
        } catch (\InvalidArgumentException $e) {
            throw new InvalidArgumentException(sprintf('Unable to parse file "%s".', $file), $e->getCode(), $e);
        }

        return simplexml_import_dom($dom, 'Rollerworks\\Component\\Search\\Metadata\\SimpleXMLElement');
    }
}
