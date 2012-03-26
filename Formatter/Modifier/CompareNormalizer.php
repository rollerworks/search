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
