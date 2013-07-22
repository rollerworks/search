<?php

/**
 * This file is part of the RollerworksRecordFilterBundle.
 *
 * (c) Sebastiaan Stok <s.stok@rollerscapes.net>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Rollerworks\Bundle\RecordFilterBundle\Formatter\Modifier;

use Rollerworks\Bundle\RecordFilterBundle\Formatter\FormatterInterface;
use Rollerworks\Bundle\RecordFilterBundle\Formatter\OptimizableInterface;
use Rollerworks\Bundle\RecordFilterBundle\Value\FilterValuesBag;
use Rollerworks\Bundle\RecordFilterBundle\FilterField;
use Rollerworks\Bundle\RecordFilterBundle\MessageBag;

/**
 * Optimizes value by filter-type implementation.
 *
 * @author Sebastiaan Stok <s.stok@rollerscapes.net>
 */
class ValueOptimizer implements ModifierInterface
{
    /**
     * @var array
     */
    protected $messages = array();

    /**
     * {@inheritdoc}
     */
    public function getModifierName()
    {
        return 'valueOptimizer';
    }

    /**
     * {@inheritdoc}
     */
    public function modFilters(FormatterInterface $formatter, MessageBag $messageBag, FilterField $filterConfig, FilterValuesBag $filterStruct, $groupIndex)
    {
        if ($filterConfig->hasType() && $filterConfig->getType() instanceof OptimizableInterface) {
            return $filterConfig->getType()->optimizeField($filterStruct, $messageBag) === false ? null : true;
        }

        return true;
    }
}
