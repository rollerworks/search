<?php

/**
 * This file is part of the RollerworksRecordFilterBundle.
 *
 * (c) Sebastiaan Stok <s.stok@rollerscapes.net>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Rollerworks\Bundle\RecordFilterBundle\Type;

use Rollerworks\Bundle\RecordFilterBundle\Formatter\ValuesToRangeInterface;

/**
 * Contains the Value and Type of an Chained value.
 *
 * @author Sebastiaan Stok <s.stok@rollerscapes.net>
 */
class DecoratedValue
{
    private $value;
    private $type;

    /**
     * Constructor
     *
     * @param mixed               $value
     * @param FilterTypeInterface $type
     */
    public function __construct($value, FilterTypeInterface $type)
    {
        $this->value = $value;
        $this->type = $type;
    }

    /**
     * @return mixed
     */
    public function getValue()
    {
        return $this->value;
    }

    /**
     * @return FilterTypeInterface|ValuesToRangeInterface|ValueMatcherInterface
     */
    public function getType()
    {
        return $this->type;
    }
}
