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

/**
 * XmlFileLoader.
 *
 * @author Sebastiaan Stok <s.stok@rollerscapes.net>
 */
class XmlFileLoader extends AbstractFileDriver
{
    /**
     * {@inheritDoc}
     */
    protected function loadMetadataFromFile(\ReflectionClass $class, $file)
    {
        $xml = $this->parseFile($file);
        $classMetadata = new MergeableClassMetadata($class->name);

        foreach ($xml as $property) {
            $propertyMetadata = new PropertyMetadata($class->name, (string) $property['id']);

            $propertyMetadata->filter_name = (string) $property['name'];
            $propertyMetadata->label       = (isset($property['label']) ? (string) $property['label'] : null);
            $propertyMetadata->required    = (isset($property['required']) ? static::parseBool($property['required']) : false);

            if (isset($property->type)) {
                if (count($property->type->children())) {
                    $params = array();

                    foreach ($property->type as $typeParam) {
                        $params[(string) $typeParam['key']] = $this->parseValue($typeParam);
                    }

                    $propertyMetadata->type = new FilterTypeConfig((string) $property->type['name'], $params);
                } else {
                    $propertyMetadata->type = new FilterTypeConfig((string) $property->type['name']);
                }

            } else {
                $propertyMetadata->type = new FilterTypeConfig(null);
            }

            $propertyMetadata->acceptRanges   = (isset($property['accept_ranges']) ? static::parseBool($property['accept_ranges']) : false);
            $propertyMetadata->acceptCompares = (isset($property['accept_compares']) ? static::parseBool($property['accept_compares']) : false);

            if (isset($property->doctrine) && isset($property->doctrine->orm)) {
                $this->setDoctrineOrm($propertyMetadata, $property->doctrine->orm);
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
        return 'xml';
    }

    /**
     * @param PropertyMetadata  $propertyMetadata
     * @param \SimpleXMLElement $property
     */
    protected function setDoctrineOrm(PropertyMetadata $propertyMetadata, \SimpleXMLElement $property)
    {
        $propertyMetadata->setDoctrineConfig('orm', new OrmConfig());

        if (!isset($property->conversion)) {
            return;
        }

        if (isset($property->conversion->field)) {
            if (count($property->conversion->field->children())) {
                $params = array();

                foreach ($property->conversion->field->children() as $typeParam) {
                    $params[(string) $typeParam['key']] = $this->parseValue($typeParam);
                }

                $propertyMetadata->getDoctrineConfig('orm')->setFieldConversion((string) $property->conversion->field['service'], $params);
            } else {
                $propertyMetadata->getDoctrineConfig('orm')->setFieldConversion((string) $property->conversion->field['service']);
            }
        }

        if (isset($property->conversion->value)) {
            if (count($property->conversion->value->children())) {
                $params = array();

                foreach ($property->conversion->value->children() as $typeParam) {
                    $params[(string) $typeParam['key']] = $this->parseValue($typeParam);
                }

                $propertyMetadata->getDoctrineConfig('orm')->setValueConversion((string) $property->conversion->value['service'], $params);
            } else {
                $propertyMetadata->getDoctrineConfig('orm')->setValueConversion((string) $property->conversion->value['service']);
            }
        }
    }

    /**
     * @param string $value
     *
     * @return boolean
     */
    protected static function parseBool($value)
    {
        if (is_bool($value)) {
            return $value;
        }

        if ('true' === (string) $value) {
            return true;
        }

        return false;
    }

    /**
     * Parses a collection of "value" XML nodes.
     *
     * @param \SimpleXMLElement $nodes The XML nodes
     *
     * @return array|boolean|float|integer
     */
    protected function parseValue(\SimpleXMLElement $nodes)
    {
        if (!count($nodes)) {
            if (isset($nodes['type'])) {
                return $this->convertValue(trim($nodes), (string) $nodes['type']);
            }

            return trim($nodes);
        }

        $values = array();

        foreach ($nodes as $node) {
            if (count($node) > 0) {
                if (count($node->value) > 0) {
                    $value = $this->parseValue($node->value);
                } else {
                    $value = array();
                }
            } else {
                $value = trim($node);

                if (isset($node['type'])) {
                    $value = $this->convertValue($value, (string) $node['type']);
                }
            }

            if (isset($node['key'])) {
                $values[(string) $node['key']] = $value;
            } else {
                $values[] = $value;
            }
        }

        return $values;
    }

    /**
     * Converts a value to a PHP type.
     *
     * @param string $value
     * @param string $type
     *
     * @return boolean|float|integer
     */
    protected function convertValue($value, $type)
    {
        if ('string' === $type) {
            return $value;
        }

        switch ($type) {
            case 'integer':
                return (int) $value;
                break;

            case 'float':
                return (float) $value;
                break;

            case 'bool':
            case 'boolean':
                return static::parseBool($value);
                break;
        }

        return $value;
    }

    /**
     * Parse a XML File.
     *
     * @author Fabien Potencier <fabien@symfony.com>
     *
     * @param string $file Path of file
     *
     * @return \SimpleXMLElement
     *
     * @throws MetadataException
     */
    protected function parseFile($file)
    {
        $internalErrors = libxml_use_internal_errors(true);
        $disableEntities = libxml_disable_entity_loader(true);
        libxml_clear_errors();

        $dom = new \DOMDocument();
        $dom->validateOnParse = true;
        if (!$dom->loadXML(file_get_contents($file), LIBXML_NONET | (defined('LIBXML_COMPACT') ? LIBXML_COMPACT : 0))) {
            libxml_disable_entity_loader($disableEntities);

            throw new MetadataException(implode("\n", $this->getXmlErrors($internalErrors)));
        }

        libxml_disable_entity_loader($disableEntities);

        if (!$dom->schemaValidate(__DIR__.'/schema/dic/filter-configuration/filter-configuration-1.0.xsd')) {
            throw new MetadataException(implode("\n", $this->getXmlErrors($internalErrors)));
        }

        $dom->normalizeDocument();

        libxml_use_internal_errors($internalErrors);

        foreach ($dom->childNodes as $child) {
            if ($child->nodeType === XML_DOCUMENT_TYPE_NODE) {
                throw new MetadataException('Document types are not allowed.');
            }
        }

        return simplexml_import_dom($dom);
    }

    /**
     * @param boolean $internalErrors
     *
     * @return array
     */
    protected function getXmlErrors($internalErrors)
    {
        $errors = array();
        foreach (libxml_get_errors() as $error) {
            $errors[] = sprintf('[%s %s] %s (in %s - line %d, column %d)',
                LIBXML_ERR_WARNING == $error->level ? 'WARNING' : 'ERROR',
                $error->code,
                trim($error->message),
                $error->file ? $error->file : 'n/a',
                $error->line,
                $error->column
            );
        }

        libxml_clear_errors();
        libxml_use_internal_errors($internalErrors);

        return $errors;
    }
}
