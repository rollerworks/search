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
}
