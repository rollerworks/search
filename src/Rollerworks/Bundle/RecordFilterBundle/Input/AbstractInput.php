<?php

/*
 * This file is part of the RollerworksRecordFilterBundle.
 *
 * (c) Sebastiaan Stok <s.stok@rollerscapes.net>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Rollerworks\Bundle\RecordFilterBundle\Input;

use Rollerworks\Bundle\RecordFilterBundle\FilterField;
use Rollerworks\Bundle\RecordFilterBundle\FieldSet;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Translation\TranslatorInterface;

/**
 * AbstractInput - provides basic functionality for an Input Class.
 *
 * @author Sebastiaan Stok <s.stok@rollerscapes.net>
 */
abstract class AbstractInput implements InputInterface
{
    /**
     * Filtering groups and there ValuesBag.
     *
     * @var array
     */
    protected $groups = array();

    /**
     * @var array
     */
    protected $labelsResolve = array();

    /**
     * Optional field alias using the translator.
     *
     * Beginning with this prefix.
     *
     * @var string
     */
    protected $aliasTranslatorPrefix;

    /**
     * @var string
     */
    protected $aliasTranslatorDomain = 'filter';

    /**
     * @var FieldSet
     */
    protected $fieldsSet;

    /**
     * @var TranslatorInterface
     */
    protected $translator;

    /**
     * @var ContainerInterface
     */
    protected $container;

    /**
     * Maximum number of values of a field (per group).
     *
     * @var integer
     */
    protected $limitValues = 100;

    /**
     * Maximum number of groups.
     *
     * @var integer
     */
    protected $limitGroups = 30;

    /**
     * Constructor.
     *
     * @param TranslatorInterface $translator
     */
    public function __construct(TranslatorInterface $translator)
    {
        $this->translator = $translator;
    }

    /**
     * {@inheritdoc}
     *
     * @return static
     */
    public function setFieldSet(FieldSet $fields = null)
    {
        $this->fieldsSet = $fields;

        return $this;
    }

    /**
     * Set the DIC container.
     *
     * @param ContainerInterface $container
     *
     * @api
     */
    public function setContainer(ContainerInterface $container = null)
    {
        $this->container = $container;
    }

    /**
     * {@inheritdoc}
     *
     * @return static
     */
    public function setField($fieldName, FilterField $config)
    {
        $fieldName = mb_strtolower($fieldName);
        if (null === $this->fieldsSet) {
            $this->fieldsSet = new FieldSet();
        }

        $this->fieldsSet->set($fieldName, $config);

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getFieldSet()
    {
        if (null === $this->fieldsSet) {
            $this->fieldsSet = new FieldSet();
        }

        return $this->fieldsSet;
    }

    /**
     * Set the maximum number of values of a field (per group).
     *
     * The default is 100.
     *
     * Note: The limit applies per group, so allowing 50 groups
     * with 300 values will result in 50 * 300 and allows for 15000 values in total!
     *
     * @param integer $limit
     *
     * @return static
     */
    public function setLimitValues($limit)
    {
        $this->limitValues = (integer) $limit;

        return $this;
    }

    /**
     * Get the maximum number of values of a field (per group).
     *
     * @return integer
     */
    public function getLimitValues()
    {
        return $this->limitValues;
    }

    /**
     * Set the maximum number of groups.
     *
     * The default is 30.
     *
     * @param integer $limit
     *
     * @return static
     */
    public function setLimitGroups($limit)
    {
        $this->limitGroups = (integer) $limit;

        return $this;
    }

    /**
     * Get the maximum number of groups.
     *
     * The default is 30.
     *
     * @return integer
     */
    public function getLimitGroups()
    {
        return $this->limitGroups;
    }

    /**
     * Sets the resolving of an field-label to name, using the translator.
     *
     * Example: product.labels.[label]
     *
     * For this to work properly a Translator instance must be registered with setTranslator()
     *
     * @param string $pathPrefix This prefix is added before every search, like: filters.labels.
     * @param string $domain     Translation domain (default is filter)
     *
     * @return static
     *
     * @throws \InvalidArgumentException
     */
    public function setLabelToFieldByTranslator($pathPrefix, $domain = 'filter')
    {
        if (!is_string($domain) || empty($domain)) {
            throw new \InvalidArgumentException('Domain must be a string and can not be empty.');
        }

        $this->aliasTranslatorPrefix = $pathPrefix;
        $this->aliasTranslatorDomain = $domain;

        return $this;
    }

    /**
     * Sets the resolving of a field label to name.
     *
     * Existing revolvings are overwritten.
     *
     * @param string       $fieldName Original field-name
     * @param string|array $label
     *
     * @return static
     */
    public function setLabelToField($fieldName, $label)
    {
        if (is_array($label)) {
            foreach ($label as $fieldLabel) {
                $this->labelsResolve[$fieldLabel] = $fieldName;
            }
        } elseif (is_string($label)) {
            $this->labelsResolve[$label] = $fieldName;
        }

        return $this;
    }

    /**
     * Gets the corresponding fieldName by label.
     *
     * @param string $label
     *
     * @return string
     */
    protected function getFieldNameByLabel($label)
    {
        $label = (function_exists('mb_strtolower') ? mb_strtolower($label) : strtolower($label));

        // Label is not aliased
        if ($this->fieldsSet->has($label)) {
            return $label;
        }

        if (isset($this->labelsResolve[$label])) {
            $fieldName = $this->labelsResolve[$label];
        } else {
            $fieldName = $this->translator->trans($this->aliasTranslatorPrefix . $label, array(), $this->aliasTranslatorDomain);

            if ($this->aliasTranslatorPrefix . $label === $fieldName) {
                $fieldName = $label;
            }
        }

        return $fieldName;
    }
}
