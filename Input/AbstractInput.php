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

use Rollerworks\RecordFilterBundle\Type\ValueMatcherInterface;
use Rollerworks\RecordFilterBundle\Type\FilterTypeInterface;
use Rollerworks\RecordFilterBundle\FilterConfig;
use Rollerworks\RecordFilterBundle\FieldsSet;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use \InvalidArgumentException;

/**
 * AbstractInput.
 *
 * Provide basic functionality for an Input Class.
 *
 * @author Sebastiaan Stok <s.stok@rollerscapes.net>
 */
abstract class AbstractInput implements InputInterface
{
    /**
     * Filtering groups and there values-bag
     *
     * @var array
     */
    protected $groups = array();

    /**
     * Field aliases.
     * An alias is kept as: alias => destination
     *
     * @var array
     */
    protected $labelsResolv = array();

    /**
     * Optional field alias using the translator.
     * Beginning with this prefix.
     *
     * @var string
     */
    protected $aliasTranslatorPrefix;

    /**
     * Optional field alias using the translator.
     * Domain to search in.
     *
     * @var string
     */
    protected $aliasTranslatorDomain = 'filter';

    /**
     * @var FieldsSet
     */
    protected $fieldsSet;

    /**
     * Translator instance
     *
     * @var \Symfony\Component\Translation\TranslatorInterface
     */
    protected $translator;

    /**
     * DIC container instance
     *
     * @var \Symfony\Component\DependencyInjection\ContainerInterface
     */
    protected $container;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->fieldsSet = new FieldsSet();
    }

    /**
     * Set the DIC container for types that need it
     *
     * @param \Symfony\Component\DependencyInjection\ContainerInterface $container
     *
     * @api
     */
    public function setContainer(ContainerInterface $container = null)
    {
        $this->container = $container;
    }

    /**
     * {@inheritdoc}
     */
    public function setField($fieldName, $label = null, FilterTypeInterface $valueType = null, $required = false, $acceptRanges = false, $acceptCompares = false)
    {
        $fieldName = mb_strtolower($fieldName);

        if (null === $label) {
            $label = $fieldName;
        }
        else {
            $label = mb_strtolower($label);
        }

        if (!empty($valueType) && $valueType instanceof ContainerAwareInterface) {
            /** @var ContainerAwareInterface $valueType */
            $valueType->setContainer($this->container);
        }

        $this->fieldsSet->set($fieldName, new FilterConfig($label, $valueType, $required, $acceptRanges, $acceptCompares));

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getFieldsConfig()
    {
        return $this->fieldsSet;
    }
}