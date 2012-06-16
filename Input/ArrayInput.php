<?php

/**
 * This file is part of the RollerworksRecordFilterBundle.
 *
 * (c) Sebastiaan Stok <s.stok@rollerscapes.net>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Rollerworks\Bundle\RecordFilterBundle\Input;

use Rollerworks\Bundle\RecordFilterBundle\Exception\ValidationException;
use Rollerworks\Bundle\RecordFilterBundle\FilterConfig;
use Rollerworks\Bundle\RecordFilterBundle\MessageBag;

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
 * FIXME Values can not be as structured array per type.
 *
 * @see FilterQuery
 *
 * @author Sebastiaan Stok <s.stok@rollerscapes.net>
 */
class ArrayInput extends FilterQuery
{
    /**
     * {@inheritdoc}
     */
    public function setInput($input)
    {
        if (!is_array($input)) {
            throw new \InvalidArgumentException('$input must be an array');
        }

        if (!isset($input[0])) {
            $input = array($input);
        }

        $this->isParsed = false;
        $this->query = $input;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getGroups()
    {
        if ($this->isParsed) {
            return $this->groups;
        }

        $this->messages = new MessageBag($this->translator);

        try {
            foreach ($this->query as $groupIndex => $values) {
                if (!ctype_digit((string) $groupIndex) || !is_array($values)) {
                    continue;
                }

                $this->groups[$groupIndex] = $this->parseFilterArray($values, $groupIndex);
            }
        } catch (ValidationException $e) {
            $this->messages->addError($e->getMessage(), $e->getParams());

            return false;
        }

        $this->isParsed = true;

        return $this->groups;
    }

    /**
     * Parse the field=value array pairs from the input.
     *
     * @param array   $input
     * @param integer $group
     *
     * @return array
     *
     * @throws ValidationException
     */
    protected function parseFilterArray(array $input, $group)
    {
        $filterPairs = array();

        foreach ($input as $label => $value) {
            if (is_array($value)) {
                continue;
            }

            $label = mb_strtolower($label);
            $name = $this->getFieldNameByLabel($label);
            $value = trim($value);

            if (!$this->fieldsSet->has($name) || strlen($value) < 1) {
                continue;
            }

            if (isset($filterPairs[$name])) {
                $filterPairs[$name] .= ',' . $value;
            } else {
                $filterPairs[$name] = $value;
            }
        }

        foreach ($this->fieldsSet->all() as $name => $filterConfig) {
            /** @var FilterConfig $filterConfig */

            if (empty($filterPairs[$name])) {
                if (true === $filterConfig->isRequired()) {
                    throw new ValidationException('required', array('{{ label }}' => $filterConfig->getLabel(), '{{ group }}' => $group+1));
                }

                continue;
            }

            $filterPairs[$name] = $this->valuesToBag($filterPairs[$name], $filterConfig, $this->parseValuesList($filterPairs[$name]), $group);
        }

        return $filterPairs;
    }
}
