<?php

/**
 * This file is part of the RollerworksRecordFilterBundle.
 *
 * (c) Sebastiaan Stok <s.stok@rollerscapes.net>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Rollerworks\Bundle\RecordFilterBundle;

use Rollerworks\Bundle\RecordFilterBundle\Type\FilterTypeInterface;
use Rollerworks\Bundle\RecordFilterBundle\Type\ValueMatcherInterface;
use Rollerworks\Bundle\RecordFilterBundle\Metadata\FilterTypeConfig;

/**
 * FilterField.
 *
 * @author Sebastiaan Stok <s.stok@rollerscapes.net>
 */
class FilterField
{
    /**
     * @var FilterTypeInterface|ValueMatcherInterface|FilterTypeConfig|null
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
    protected $propertyClass;

    /**
     * @var string
     */
    protected $propertyField;

    /**
     * Constructor.
     *
     * @param string                                                          $label
     * @param FilterTypeInterface|ValueMatcherInterface|FilterTypeConfig|null $type
     * @param boolean                                                         $required
     * @param boolean                                                         $acceptRanges
     * @param boolean                                                         $acceptCompares
     */
    public function __construct($label, $type = null, $required = false, $acceptRanges = false, $acceptCompares = false)
    {
        $this->label      = mb_strtolower((string) $label);
        $this->filterType = $type;

        $this->acceptRanges   = (boolean) $acceptRanges;
        $this->acceptCompares = (boolean) $acceptCompares;
        $this->required       = (boolean) $required;
    }

    /**
     * Creates a new FilterField object.
     *
     * @param string                                                          $label
     * @param FilterTypeInterface|ValueMatcherInterface|FilterTypeConfig|null $type
     * @param boolean                                                         $required
     * @param boolean                                                         $acceptRanges
     * @param boolean                                                         $acceptCompares
     *
     * @return FilterField
     */
    public static function create($label, $type = null, $required = false, $acceptRanges = false, $acceptCompares = false)
    {
        return new self($label, $type, $required, $acceptRanges, $acceptCompares);
    }

    /**
     * Set the label of the field.
     *
     * @param string $label
     */
    public function setLabel($label)
    {
        $this->label = mb_strtolower((string) $label);
    }

    /**
     * Set the Property reference class-name and field.
     *
     * This can be either an ORM Entity class or ODM Document.
     *
     * @param string $class
     * @param string $field
     *
     * @return FilterField
     */
    public function setPropertyRef($class, $field)
    {
        $this->propertyClass = $class;
        $this->propertyField = $field;

        return $this;
    }

    /**
     * Get the Entity class-name for reading mapping.
     *
     * @return string
     */
    public function getPropertyRefClass()
    {
        return $this->propertyClass;
    }

    /**
     * Get the Entity field for reading mapping.
     *
     * @return string
     */
    public function getPropertyRefField()
    {
        return $this->propertyField;
    }

    /**
     * Get the type of the filter.
     *
     * @return FilterTypeInterface|ValueMatcherInterface|FilterTypeConfig|mixed|null
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
     * Returns whether an filter-type is registered.
     *
     * @return boolean
     */
    public function hasType()
    {
        return !empty($this->filterType);
    }

    /**
     * Returns whether ranges are accepted.
     *
     * @return boolean
     */
    public function acceptRanges()
    {
        return $this->acceptRanges;
    }

    /**
     * Returns whether comparisons are accepted.
     *
     * @return boolean
     */
    public function acceptCompares()
    {
        return $this->acceptCompares;
    }

    /**
     * Returns whether the field is required.
     *
     * @return boolean
     */
    public function isRequired()
    {
        return $this->required;
    }
}
