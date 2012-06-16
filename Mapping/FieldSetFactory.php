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

use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Translation\TranslatorInterface;
use Rollerworks\Bundle\RecordFilterBundle\FilterConfig;
use Rollerworks\Bundle\RecordFilterBundle\FieldSet;
use Metadata\MetadataFactoryInterface;

/**
 * Imports the filtering configuration of metadata to the an FieldsSet.
 *
 * @author Sebastiaan Stok <s.stok@rollerscapes.net>
 */
class FieldSetFactory
{
    /**
     * @var MetadataFactoryInterface
     */
    protected $metadataFactory;

    /**
     * @var ContainerInterface
     */
    protected $container;

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
     */
    public function __construct(MetadataFactoryInterface $metadataFactory, TranslatorInterface $translator)
    {
        $this->metadataFactory = $metadataFactory;
    }

    /**
     * Set the DIC container for types that need it
     *
     * @param ContainerInterface $container
     */
    public function setContainer(ContainerInterface $container = null)
    {
        $this->container = $container;
    }

    /**
     * Set the resolving of an field name to label, using the translator beginning with prefix.
     *
     * Example: product.fields.[field]
     *
     * For this to work properly a Translator must be registered with setTranslator()
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
     * @param array         $options     Array with options per filter-name
     *
     * @return self
     *
     * @see \Symfony\Component\OptionsResolver\OptionsResolver
     *
     * @throws \InvalidArgumentException When $class is not an object or string
     * @throws \RuntimeException
     */
    public function importConfigFromClass(FieldSet $fieldsSet, $class, array $limitFields = array(), array $options = array())
    {
        if (!is_object($class) && !is_string($class)) {
            throw new \InvalidArgumentException('No legal class provided');
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
                $r = new \ReflectionClass($propertyMetadata->type);

                if ($r->implementsInterface('Rollerworks\\Bundle\\RecordFilterBundle\\Type\\ConfigurableInterface')) {
                    if (isset($options[$propertyMetadata->filter_name])) {
                        $optionsResolver = new OptionsResolver();
                        call_user_func(array($propertyMetadata->type, 'setOptions'), $optionsResolver);
                        $optionsResolver->setDefaults($propertyMetadata->params);

                        $type = $r->newInstanceArgs($optionsResolver->resolve($options[$propertyMetadata->filter_name]));
                    } else {
                        $type = $r->newInstanceArgs($propertyMetadata->params);
                    }
                } else {
                    $type = $r->newInstance();
                }

                if ($r->implementsInterface('Symfony\Component\DependencyInjection\ContainerAwareInterface')) {
                    if (null === $this->container) {
                        throw new \RuntimeException('Filter-type "%s" requires a DI container. But none is set.');
                    }

                    $type->setContainer($this->container);
                }
            }

            $config = new FilterConfig($label, $type, $propertyMetadata->required, $propertyMetadata->acceptRanges, $propertyMetadata->acceptCompares);
            $config->setPropertyRef($class, $propertyMetadata->name);
            $fieldsSet->set($propertyMetadata->filter_name, $config);
        }

        return $this;
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
