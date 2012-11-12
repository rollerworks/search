<?php

/**
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
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
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
     * @return self
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
     * @return self
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
}
