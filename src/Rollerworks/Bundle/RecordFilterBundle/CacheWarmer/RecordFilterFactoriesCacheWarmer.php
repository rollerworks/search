<?php

/*
 * This file is part of the RollerworksRecordFilterBundle package.
 *
 * (c) Sebastiaan Stok <s.stok@rollerscapes.net>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Rollerworks\Bundle\RecordFilterBundle\CacheWarmer;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\CacheWarmer\CacheWarmerInterface;
use Rollerworks\Bundle\RecordFilterBundle\Metadata\FilterTypeConfig;
use Rollerworks\Bundle\RecordFilterBundle\Metadata\PropertyMetadata;
use Rollerworks\Bundle\RecordFilterBundle\FilterField;
use Rollerworks\Bundle\RecordFilterBundle\FieldSet;
use Metadata\MetadataFactoryInterface;

/**
 * Generates the Classes for the RecordFilter.
 *
 * The classes generated are depended on the configuration of the application.
 * By default nothing is generated.
 *
 * @author Sebastiaan Stok <s.stok@rollerscapes.net>
 */
class RecordFilterFactoriesCacheWarmer implements CacheWarmerInterface
{
    private $container;
    private $metadataFactory;

    /**
     * Constructor.
     *
     * @param ContainerInterface       $container
     * @param MetadataFactoryInterface $metadataFactory
     */
    public function __construct(ContainerInterface $container, MetadataFactoryInterface $metadataFactory)
    {
        $this->metadataFactory = $metadataFactory;
        $this->container = $container;
    }

    /**
     * Warms up the cache.
     *
     * @param string $cacheDir The cache directory
     *
     * @throws \RuntimeException         When the directory can not be created
     * @throws \InvalidArgumentException When the directory is invalid
     */
    public function warmUp($cacheDir)
    {
        if (!strlen($this->container->getParameter('rollerworks_record_filter.filters_directory'))) {
            throw new \InvalidArgumentException('You must configure a filters RecordFilter directory (when record_filter is activated in the services file). See docs for details');
        }

        // @codeCoverageIgnoreStart
        if (!file_exists($sFilterDirectory = $this->container->getParameter('rollerworks_record_filter.filters_directory'))) {
            if (false === @mkdir($sFilterDirectory, 0777, true)) {
                throw new \RuntimeException(sprintf('Unable to create the RecordFilters directory "%s".', $sFilterDirectory));
            }
        } elseif (!is_writable($sFilterDirectory)) {
            throw new \RuntimeException(sprintf('The RecordFilters directory "%s" is not writable for the current system user.', $sFilterDirectory));
        }
        // @codeCoverageIgnoreEnd

        if ($this->container->getParameter('rollerworks_record_filter.factories.fieldset.auto_generate')) {
            $fieldSets = $this->createFieldSets(unserialize($this->container->getParameter('rollerworks_record_filter.fieldsets')));

            $this->container->get('rollerworks_record_filter.fieldset_factory')->generateClasses($fieldSets);

            if ($this->container->hasParameter('rollerworks_record_filter.factories.doctrine.orm.wherebuilder.auto_generate') && $this->container->getParameter('rollerworks_record_filter.factories.doctrine.orm.wherebuilder.auto_generate')) {
                $this->container->get('rollerworks_record_filter.doctrine.orm.wherebuilder_factory')->generateClasses($fieldSets);
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    public function isOptional()
    {
        return false;
    }

    /**
     * @param array $fieldSets
     *
     * @return FieldSet[]
     */
    private function createFieldSets(array $fieldSets)
    {
        $fieldSetsArray = array();

        foreach ($fieldSets as $setName => $setData) {
            $fieldSet = new FieldSet($setName);

            foreach ($setData['import'] as $import) {
                $this->importConfigFromClass($fieldSet, $import['class'], $import['include_fields'], $import['exclude_fields']);
            }

            foreach ($setData['fields'] as $fieldName => $field) {
                $type = null;
                $label = null === $field['label'] ? $fieldName : $field['label'];

                if (!empty($field['type'])) {
                    $type = new FilterTypeConfig($field['type']['name'], $field['type']['params']);
                }

                $filterField = new FilterField($label, $type, $field['required'], $field['accept_ranges'], $field['accept_compares']);

                if (isset($field['ref'])) {
                    $filterField->setPropertyRef($field['ref']['class'], $field['ref']['property']);
                }

                $fieldSet->set($fieldName, $filterField);
            }

            $fieldSetsArray[$setName] = $fieldSet;
        }

        return $fieldSetsArray;
    }

    /**
     * @param FieldSet $fieldsSet
     * @param string   $class
     * @param array    $includeFields
     * @param array    $excludeFields
     */
    private function importConfigFromClass(FieldSet $fieldsSet, $class, array $includeFields = array(), array $excludeFields = array())
    {
        // Include prevails over excludes
        if (!empty($includeFields) && !empty($excludeFields)) {
            $excludeFields = array();
        }

        $classMetadata = $this->metadataFactory->getMetadataForClass($class);

        foreach ($classMetadata->propertyMetadata as $propertyMetadata) {
            if (!$propertyMetadata instanceof PropertyMetadata || null === $propertyMetadata->filter_name) {
                continue;
            }

            /** @var PropertyMetadata $propertyMetadata */
            if (!empty($includeFields) && !in_array($propertyMetadata->filter_name, $includeFields)) {
                continue;
            } elseif (in_array($propertyMetadata->filter_name, $excludeFields)) {
                continue;
            }

            if (null !== $propertyMetadata->type) {
                $type = $propertyMetadata->type;
            } else {
                $type = new FilterTypeConfig(null);
            }

            $config = new FilterField(
                (null !== $propertyMetadata->label ? $propertyMetadata->label : $propertyMetadata->filter_name),
                $type,
                $propertyMetadata->required,
                $propertyMetadata->acceptRanges,
                $propertyMetadata->acceptCompares
            );

            $config->setPropertyRef($class, $propertyMetadata->name);
            $fieldsSet->set($propertyMetadata->filter_name, $config);
        }
    }
}
