<?php
/**
 * This file is part of the RollerworksRecordFilterBundle.
 *
 * (c) Rollerscapes
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU Lesser General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Lesser General Public License for more details.
 *
 * You should have received a copy of the GNU Lesser General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 * @link    http://projects.rollerscapes.net/RollerFramework
 * @license http://www.opensource.org/licenses/lgpl-license.php LGPL
 */

namespace Rollerworks\RecordFilterBundle\Formatter;

use Rollerworks\RecordFilterBundle\Formatter\ValueMatcherInterface;
use Rollerworks\RecordFilterBundle\Formatter\FilterType;

/**
 * Filter-field configuration class.
 * Holds the configuration options for filter-field.
 *
 * @author Sebastiaan Stok <s.stok@rollerscapes.net>
 */
class FilterConfig
{
    /**
     * @var null|FilterType|ValueMatcherInterface
     */
    protected $filterType;

    /**
     * @var bool
     */
    protected $acceptRanges;

    /**
     * @var bool
     */
    protected $acceptCompares;

    /**
     * @var bool
     */
    protected $required;

    /**
     * Constructor
     *
     * @param FilterType|ValueMatcherInterface|null  $type
     * @param bool                          $required
     * @param bool                          $acceptRanges
     * @param bool                          $acceptCompares
     */
    public function __construct($type = null, $required = false, $acceptRanges = false, $acceptCompares = false)
    {
        $this->filterType     = $type;
        $this->acceptRanges   = $acceptRanges;
        $this->acceptCompares = $acceptCompares;
        $this->required       = $required;
    }

    /**
     * Get the type of the filter.
     *
     * @return FilterType|ValueMatcherInterface|null
     */
    public function getType()
    {
        return $this->filterType;
    }

    /**
     * Returns whether an filter-type is registered
     *
     * @return bool
     */
    public function hasType()
    {
        return !empty($this->filterType);
    }

    /**
     * Returns whether ranges are accepted
     *
     * @return bool
     */
    public function acceptRanges()
    {
        return $this->acceptRanges;
    }

    /**
     * Returns whether comparisons are accepted
     *
     * @return bool
     */
    public function acceptCompares()
    {
        return $this->acceptCompares;
    }

    /**
     * Returns whether the field is required
     *
     * @return bool
     */
    public function isRequired()
    {
        return $this->required;
    }
}