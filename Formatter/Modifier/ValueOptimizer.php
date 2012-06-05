<?php

/**
 * This file is part of the RollerworksRecordFilterBundle.
 *
 * (c) Sebastiaan Stok <s.stok@rollerscapes.net>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Rollerworks\RecordFilterBundle\Formatter\Modifier;

use Rollerworks\RecordFilterBundle\Formatter\FormatterInterface;
use Rollerworks\RecordFilterBundle\MessageBag;
use Rollerworks\RecordFilterBundle\Formatter\OptimizableInterface;
use Rollerworks\RecordFilterBundle\Value\FilterValuesBag;
use Rollerworks\RecordFilterBundle\FilterConfig;

/**
 * Optimizes the value by there own implementation.
 *
 * @author Sebastiaan Stok <s.stok@rollerscapes.net>
 */
class ValueOptimizer implements ModifierInterface
{
    /**
     * Optimizer messages
     *
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
    public function modFilters(FormatterInterface $formatter, MessageBag $messageBag, FilterConfig $filterConfig, FilterValuesBag $filterStruct, $groupIndex)
    {
        if ($filterConfig->hasType() && $filterConfig->getType() instanceof OptimizableInterface) {
            return $filterConfig->getType()->optimizeField($filterStruct, $messageBag);
        } else {
            return true;
        }
    }
}
