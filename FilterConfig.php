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
     * Constructor
     *
     * @param FilterTypeInterface|ValueMatcherInterface|null  $type
     * @param boolean                                         $required
     * @param boolean                                         $acceptRanges
     * @param boolean                                         $acceptCompares
     */
    public function __construct($type = null, $required = false, $acceptRanges = false, $acceptCompares = false)
    {
        $this->filterType     = $type;
        $this->acceptRanges   = (bool) $acceptRanges;
        $this->acceptCompares = (bool) $acceptCompares;
        $this->required       = (bool) $required;
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