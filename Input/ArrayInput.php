<?php

/**
 * This file is part of the RollerworksRecordFilterBundle.
 *
 * (c) Sebastiaan Stok <s.stok@rollerscapes.net>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Rollerworks\RecordFilterBundle\Input;

use Rollerworks\RecordFilterBundle\Exception\ReqFilterException;
use Rollerworks\RecordFilterBundle\Exception\ValidationException;
use Rollerworks\RecordFilterBundle\Type\ValueMatcherInterface;
use Rollerworks\RecordFilterBundle\FilterConfig;
use Rollerworks\RecordFilterBundle\Type\FilterTypeInterface;
use Rollerworks\RecordFilterBundle\Value\FilterValuesBag;
use Rollerworks\RecordFilterBundle\Value\SingleValue;
use Rollerworks\RecordFilterBundle\Value\Compare;
use Rollerworks\RecordFilterBundle\Value\Range;

use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Translation\TranslatorInterface;
use \InvalidArgumentException;

/**
 * ArrayInput.
 *
 * Accept input in an PHP Array format.
 *
 * If the value is an array and key is numeric its threaten as a group.
 * And its value must be an array containing the input and there values (as comma separated string).
 *
 * If the key is not numeric its an field-name.
 *
 * Values can not be as structured array per type.
 *
 * @see FilterQuery
 *
 * @author Sebastiaan Stok <s.stok@rollerscapes.net>
 */
class ArrayInput extends FilterQuery
{
    /**
     * Set the filter input
     *
     * @param array $input
     * @return ArrayInput
     *
     * @throws \InvalidArgumentException
     */
    public function setInput($input)
    {
        if (!is_array($input)) {
            throw new \InvalidArgumentException('$input must be an array');
        }

        $this->isParsed = false;
        $this->query    = $input;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getGroups()
    {
        if (false === $this->isParsed) {
            if (isset($this->query[0])) {
                foreach ($this->query as $groupIndex => $values) {
                    if (!ctype_digit((string) $groupIndex) || !is_array($values)) {
                        continue;
                    }

                    $this->groups[$groupIndex] = $this->parseFilterArray($values);
                }
            }
            else {
                $this->groups[0] = $this->parseFilterArray($this->query);
            }
        }

        return $this->groups;
    }

    /**
     * Parse the field=value array pairs from the input.
     *
     * @param array $input
     * @return array
     *
     * @throws \Rollerworks\RecordFilterBundle\Exception\ReqFilterException
     */
    protected function parseFilterArray(array $input)
    {
        $filterPairs = array();

        foreach ($input as $label => $value) {
            if (is_array($value)) {
                continue;
            }

            $label = mb_strtolower($label);
            $name  = $this->getFieldNameByLabel($label);
            $value = trim($value);

            if (!$this->fieldsSet->has($name) || strlen($value) < 1) {
                continue;
            }

            if (isset($filterPairs[$name])) {
                $filterPairs[$name] .= ',' . $value;
            }
            else {
                $filterPairs[$name] = $value;
            }
        }

        foreach ($this->fieldsSet->all() as $name => $filterConfig) {
            /** @var FilterConfig $filterConfig */

            if (empty($filterPairs[$name])) {
                if (true === $filterConfig->isRequired()) {
                    throw new ReqFilterException($filterConfig->getLabel());
                }

                continue;
            }

            $filterPairs[$name] = $this->valuesToBag($filterConfig->getLabel(), $filterPairs[$name], $filterConfig, $this->parseValuesList($filterPairs[$name]));
        }

        return $filterPairs;
    }
}