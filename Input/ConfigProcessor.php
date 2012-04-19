<?php

/**
 * This file is part of the RollerworksRecordFilterBundle.
 *
 * (c) Sebastiaan Stok <s.stok@rollerscapes.net>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Rollerworks\RecordFilterBundle\Input;

use Rollerworks\RecordFilterBundle\Metadata\AbstractConfigProcessor;
use Rollerworks\RecordFilterBundle\FilterConfig;
use Rollerworks\RecordFilterBundle\FieldsSet;

use Symfony\Component\Translation\TranslatorInterface;
use Metadata\MetadataFactoryInterface;

/**
 * Imports the configuration from Entities metadata to the fieldsSet instance.
 *
 * @author Sebastiaan Stok <s.stok@rollerscapes.net>
 */
class ConfigProcessor extends AbstractConfigProcessor
{
    /**
     * Translator instance
     *
     * @var \Symfony\Component\Translation\TranslatorInterface
     */
    protected $translator;

    /**
     * Optional field alias using the translator.
     * Beginning with this prefix.
     *
     * @var string
     */
    protected $translatorPrefix;

    /**
     * Optional field alias using the translator.
     * Domain to search in.
     *
     * @var string
     */
    protected $translatorDomain = 'filter';

    /**
     * Set the translator instance, for aliases by translator
     *
     * @param \Symfony\Component\Translation\TranslatorInterface $translator
     *
     * @api
     */
    public function setTranslator(TranslatorInterface $translator)
    {
        $this->translator = $translator;
    }

    /**
     * Set the resolving of an field name to label, using the translator beginning with prefix.
     *
     * Example: product.fields.[field]
     *
     * For this to work properly a Translator must be registered with setTranslator()
     *
     * @param string $pathPrefix    This prefix is added before every search, like filters.labels.
     * @param string $domain        Default is filter
     */
    public function setLabelToFieldByTranslator($pathPrefix, $domain = 'filter')
    {
        if (!is_string($pathPrefix) || empty($pathPrefix)) {
            throw new \InvalidArgumentException('Prefix must be an string and can not be empty');
        }

        if (!is_string($domain) || empty($domain)) {
            throw new \InvalidArgumentException('Domain must be an string and can not be empty');
        }

        $this->translatorPrefix = $pathPrefix;
        $this->translatorDomain = $domain;
    }

    /**
     * Fill the FieldsSet with the configuration of an Entity.
     *
     * @param FieldsSet         $fieldsSet
     * @param object|string     $entity Entity object or full class-name
     * @return ConfigProcessor
     *
     * @throws \InvalidArgumentException When $entity is not an object or string
     * @throws \RuntimeException
     *
     * @todo Allow an short notation (BundleName:Entity) (Need some good feedback on this first)
     */
    public function fillInputConfig(FieldsSet $fieldsSet, $entity)
    {
        if (!is_object($entity) && !is_string($entity)) {
            throw new \InvalidArgumentException('No legal entity provided');
        }

        if (is_object($entity)) {
            $entity = get_class($entity);
        }

        $classMetadata = $this->metadataFactory->getMetadataForClass($entity);

        /**
         * @var \Metadata\ClassMetadata $metadata
         * @var \Rollerworks\RecordFilterBundle\Metadata\PropertyMetadata $propertyMetadata
         */

        foreach ($classMetadata->propertyMetadata as $propertyMetadata) {
            /* @var $propertyMetadata \Rollerworks\RecordFilterBundle\Metadata\PropertyMetadata */
            if (isset($propertyMetadata->filter_name)) {
                $type  = null;
                $label = $this->getFieldLabel($propertyMetadata->filter_name);

                if (null !== $propertyMetadata->type) {
                    if (method_exists($propertyMetadata->type, '__construct')) {
                        $r = new \ReflectionClass($propertyMetadata->type);

                        $propertyMetadata->params['_label'] = $label;
                        $type = $r->newInstanceArgs($this->doGetArguments($propertyMetadata->params, $propertyMetadata->type, $r->getMethod('__construct')->getParameters()));
                    }
                    else {
                        $type = new $propertyMetadata->type;
                    }
                }

                $fieldsSet->set($propertyMetadata->filter_name, new FilterConfig($label, $type, $propertyMetadata->required, $propertyMetadata->acceptRanges, $propertyMetadata->acceptCompares));
            }
        }

        return $this;
    }

    /**
     * Get the label by fieldName
     *
     * @param string $field
     * @return string
     */
    protected function getFieldLabel($field)
    {
        if (null !== $this->translatorPrefix && null === $this->translator) {
            throw new \RuntimeException('No translator registered.');
        }

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
