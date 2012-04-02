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
use Rollerworks\RecordFilterBundle\Formatter\FilterConfig;
use Rollerworks\RecordFilterBundle\FilterStruct;

/**
 * Validate and formats the filters.
 * After this the values can be considered valid.
 *
 * @author Sebastiaan Stok <s.stok@rollerscapes.net>
 */
class CompareNormalizer implements PostModifierInterface
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
        return 'compareNormalizer';
    }

    /**
     * {@inheritdoc}
     */
    public function modFilters(FormatterInterface $formatter, FilterConfig $filterConfig, FilterStruct $filterStruct, $groupIndex)
    {
        $this->messages = array();

        if (!$filterStruct->hasCompares()) {
            return true;
        }

        $compares = $filterStruct->getCompares();

        foreach ($compares as $compare) {
            if ('=' === substr($compare->getOperator(), -1)) {
                $comparisonIndex = array_search(substr($compare->getOperator(), 0, 1) . $compare->getValue(), $compares);

                if ($comparisonIndex !== false) {
                    $this->addMsg('redundant_comparison', array(
                        '%value%'      => $compares[$comparisonIndex]->getOperator() . '"' . $compares[$comparisonIndex]->getOriginalValue() . '"',
                        '%comparison%' => $compare->getOperator(),
                    ));

                    unset($compares[ $comparisonIndex ]);
                    $filterStruct->removeCompare($comparisonIndex);
                }
            }
        }

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function getMessages()
    {
        return $this->messages;
    }

    /**
     * Add an new message to the list
     *
     * @param string  $transMessage
     * @param array   $params
     */
    protected function addMsg($transMessage, $params = array())
    {
        $this->messages[] = array($transMessage, $params);
    }
}
