<?php

/**
 * This file is part of the RollerworksRecordFilterBundle.
 *
 * (c) Sebastiaan Stok <s.stok@rollerscapes.net>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Rollerworks\RecordFilterBundle;

use Rollerworks\RecordFilterBundle\Type\FilterTypeInterface;
use Rollerworks\RecordFilterBundle\Type\ValueMatcherInterface;

/**
 * FilterConfig.
 *
 * Holds the configuration options of an field.
 *
 * @author Sebastiaan Stok <s.stok@rollerscapes.net>
 */
class FilterConfig
{
    /**
     * @var null|FilterTypeInterface|ValueMatcherInterface
     */
    protected $filterType;

    /**
     * @var string
     */
    protected $label;

    /**
     * @var boolean
     */
    protected $acceptRanges;

    /**
     * @var boolean
     */
    protected $acceptCompares;

    /**
     * @var boolean
     */
    protected $required;

    /**
     * @var string
     */
    protected $class;

    /**
     * @var string
     */
    protected $column;

    /**
     * Constructor
     *
     * @param string                                          $label
     * @param FilterTypeInterface|ValueMatcherInterface|null  $type
     * @param boolean                                         $required
     * @param boolean                                         $acceptRanges
     * @param boolean                                         $acceptCompares
     */
    public function __construct($label, $type = null, $required = false, $acceptRanges = false, $acceptCompares = false)
    {
        $this->label          = (string) $label;
        $this->filterType     = $type;
        $this->acceptRanges   = (boolean) $acceptRanges;
        $this->acceptCompares = (boolean) $acceptCompares;
        $this->required       = (boolean) $required;
    }

    /**
     * Set the Entity class-name for reading mapping.
     *
     * @param string $class
     */
    public function setEntityClass($class)
    {
        $this->class = $class;
    }

    /**
     * Get the Entity class-name for reading mapping.
     *
     * @return string
     */
    public function getEntityClass()
    {
        return $this->class;
    }

    /**
     * Set the Entity column for reading mapping.
     *
     * @param string $column
     */
    public function setEntityColumn($column)
    {
        $this->column = $column;
    }

    /**
     * Get the Entity column for reading mapping.
     *
     * @return string
     */
    public function getEntityField()
    {
        return $this->column;
    }

    /**
     * Get the type of the filter.
     *
     * @return FilterTypeInterface|ValueMatcherInterface|object|null
     */
    public function getType()
    {
        return $this->filterType;
    }

    /**
     * Get the label of the filter.
     *
     * @return string
     */
    public function getLabel()
    {
        return $this->label;
    }

    /**
     * Returns whether an filter-type is registered
     *
     * @return boolean
     */
    public function hasType()
    {
        return !empty($this->filterType);
    }

    /**
     * Returns whether ranges are accepted
     *
     * @return boolean
     */
    public function acceptRanges()
    {
        return $this->acceptRanges;
    }

    /**
     * Returns whether comparisons are accepted
     *
     * @return boolean
     */
    public function acceptCompares()
    {
        return $this->acceptCompares;
    }

    /**
     * Returns whether the field is required
     *
     * @return boolean
     */
    public function isRequired()
    {
        return $this->required;
    }
}