<?php

/**
 * This file is part of the RollerworksRecordFilterBundle.
 *
 * (c) Sebastiaan Stok <s.stok@rollerscapes.net>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Rollerworks\Bundle\RecordFilterBundle\Mapping;

use Symfony\Component\Translation\TranslatorInterface;
use Rollerworks\Bundle\RecordFilterBundle\Type\FilterTypeInterface;
use Rollerworks\Bundle\RecordFilterBundle\Type\ConfigurableTypeInterface;
use Rollerworks\Bundle\RecordFilterBundle\Factory\FilterTypeFactory;
use Rollerworks\Bundle\RecordFilterBundle\Mapping\FilterTypeConfig;
use Rollerworks\Bundle\RecordFilterBundle\FilterField;
use Rollerworks\Bundle\RecordFilterBundle\FieldSet;
use Metadata\MetadataFactoryInterface;

/**
 * Imports the filtering configuration of metadata to the an FieldsSet.
 *
 * @author Sebastiaan Stok <s.stok@rollerscapes.net>
 */
class FieldSetBuilder
{
    /**
     * @var MetadataFactoryInterface
     */
    protected $metadataFactory;

    /**
     * @var FilterTypeFactory
     */
    protected $typeFactory;

    /**
     * @var TranslatorInterface
     */
    protected $translator;

    /**
     * @var string
     */
    protected $translatorPrefix;

    /**
     * @var string
     */
    protected $translatorDomain = 'filter';

    /**
     * Constructor.
     *
     * @param MetadataFactoryInterface $metadataFactory
     * @param TranslatorInterface      $translator
     * @param FilterTypeFactory        $typeFactory
     */
    public function __construct(MetadataFactoryInterface $metadataFactory, TranslatorInterface $translator, FilterTypeFactory $typeFactory)
    {
        $this->translator = $translator;
        $this->metadataFactory = $metadataFactory;
        $this->typeFactory = $typeFactory;
    }

    /**
     * Set the resolving of an field name to label, using the translator beginning with prefix.
     *
     * Example: product.fields.[field]
     *
     * @param string $pathPrefix This prefix is added before every search, like filters.labels.
     * @param string $domain     Default is filter
     *
     * @return self
     *
     * @throws \InvalidArgumentException
     */
    public function setFieldToLabelByTranslator($pathPrefix, $domain = 'filter')
    {
        if (!is_string($pathPrefix) || empty($pathPrefix)) {
            throw new \InvalidArgumentException('Prefix must be an string and can not be empty');
        }

        if (!is_string($domain) || empty($domain)) {
            throw new \InvalidArgumentException('Domain must be an string and can not be empty');
        }

        $this->translatorPrefix = $pathPrefix;
        $this->translatorDomain = $domain;

        return $this;
    }

    /**
     * Imports the FieldSet with the filtering-configuration of an Class.
     *
     * @param FieldSet      $fieldsSet
     * @param object|string $class       Entity object or full class-name
     * @param array         $limitFields Only imports these fields (per filter-name)
     *
     * @return self
     *
     * @throws \InvalidArgumentException When $class is not an object or string
     * @throws \RuntimeException
     */
    public function importConfigFromClass(FieldSet $fieldsSet, $class, array $limitFields = array())
    {
        if (!is_object($class) && !is_string($class)) {
            throw new \InvalidArgumentException('No legal class provided.');
        }

        if (is_object($class)) {
            $class = get_class($class);
        }

        $classMetadata = $this->metadataFactory->getMetadataForClass($class);

        /**
         * @var \Metadata\ClassMetadata $metadata
         * @var PropertyMetadata $propertyMetadata
         */
        foreach ($classMetadata->propertyMetadata as $propertyMetadata) {
            if (!$propertyMetadata instanceof PropertyMetadata) {
                continue;
            }

            if (!empty($limitFields) && !in_array($propertyMetadata->filter_name, $limitFields)) {
                continue;
            }

            $type = null;
            $label = $this->getFieldLabel($propertyMetadata->filter_name);

            if (null !== $propertyMetadata->type) {
                $type = $this->createNewType($propertyMetadata->type);
            }

            $config = new FilterField($label, $type, $propertyMetadata->required, $propertyMetadata->acceptRanges, $propertyMetadata->acceptCompares);
            $config->setPropertyRef($class, $propertyMetadata->name);
            $fieldsSet->set($propertyMetadata->filter_name, $config);
        }

        return $this;
    }

    /**
     * @param FilterTypeConfig|FilterTypeConfig[] $type
     *
     * @return FilterTypeInterface
     *
     * @throws \InvalidArgumentException on invalid type
     */
    protected function createNewType($type)
    {
        // TODO TypeChain object
        if (is_array($type)) {
            return null;
        }

        if (!$type instanceof FilterTypeConfig) {
            throw new \InvalidArgumentException('Type must be an array of FilterTypeConfig objects or an single FilterTypeConfig object.');
        }

        $typeInstance = $this->typeFactory->newInstance($type->getName());

        if ($type->hasParams() && $typeInstance instanceof ConfigurableTypeInterface) {
            $typeInstance->setOptions($type->getParams());
        }

        return $typeInstance;
    }

    /**
     * Get the label by fieldName.
     *
     * @param string $field
     *
     * @return string
     *
     * @throws \RuntimeException
     */
    protected function getFieldLabel($field)
    {
        $label = $field;

        if (null !== $this->translatorPrefix) {
            $label = $this->translator->trans($this->translatorPrefix . $field, array(), $this->translatorDomain);

            if ($this->translatorPrefix . $field === $label) {
                $label = $field;
            }
        }

        return $label;
    }
}
