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

/**
 * RecordFilter Array input class.
 *
 * Provide the filtering preference by an PHP associative array.
 *
 * Array key is only 'used' when they begin with alphabetic an character (in unicode).
 * Array keys names contain dots or dashes (except underscore) are also ignored.
 *
 * If the value is an array and key is numeric its threaten as an or-group.
 * An array key containing an @ is also seen as or group, like: '@field' => 'value1,1-2'
 *
 * @author Sebastiaan Stok <s.stok@rollerscapes.net>
 */
class ArrayInput extends AbstractInput
{
    /**
     * Constructor.
     *
     * @param array $filters
     * @param array $ignoreFields Optional list of array-keys to ignore (integers must be as string)
     */
    public function __construct($filters, $ignoreFields = array())
    {
        foreach ($filters as $key => $groupValue) {
            $key = mb_strtolower((string) $key);

            if (in_array($key, $ignoreFields)) {
                continue;
            }

            if (is_array($groupValue) && !ctype_digit($key)) {
                throw new \UnexpectedValueException('Value is an array but the key does not seem numeric, consider adding "' . $key . '" to the ignore list.');
            }
            elseif (!is_array($groupValue) && ctype_digit($key)) {
                throw new \UnexpectedValueException('Value is not an array but the key seems numeric, consider adding "' . $key . '" to the ignore list.');
            }

            if (ctype_digit($key)) {
                $groupIndex = $key;

                foreach ($groupValue as $fieldname => $value) {
                    if (!$this->isFieldname($fieldname)) {
                        continue;
                    }

                    $fieldname = mb_strtolower($fieldname);

                    if (is_array($value)) {
                        throw new \UnexpectedValueException('Field value of "' . $fieldname . '" in group ' . $key . ' must not be an array.');
                    }

                    if (isset($this->groups[$groupIndex][$fieldname])) {
                        $this->groups[$groupIndex][$fieldname] .= ',' . $value;
                    }
                    else {
                        $this->groups[$groupIndex][$fieldname] = $value;
                    }
                }

                continue;
            }

            $groupPos = mb_strpos($key, '@');

            if (false !== $groupPos) {
                list($groupIndex, $fieldname) = explode('@', $key, 2);
            }
            else {
                $groupIndex = 0;
                $fieldname  = $key;
            }

            $fieldname = mb_strtolower($fieldname);

            if (isset($this->groups[$groupIndex][$fieldname])) {
                $this->groups[$groupIndex][$fieldname] .= ',' . $groupValue;
            }
            else {
                $this->groups[$groupIndex][$fieldname] = $groupValue;
            }
        }

        $this->hasGroups = count($this->groups) > 0;
    }

    /**
     * Set/overwrite the raw-value for the field
     *
     * @param string     $field
     * @param string     $value
     * @param integer    $group    Optional group-index (default is 0 which is the first group)
     * @return \Rollerworks\RecordFilterBundle\Input\ArrayInput
     */
    public function setValue($field, $value, $group = 0)
    {
        if (!$this->isFieldname($field)) {
            throw new \InvalidArgumentException('$field is not an legal filter-field.');
        }
        elseif (!is_string($value)) {
            throw new \InvalidArgumentException('$value must be an string value.');
        }
        elseif (!is_integer($group) || $group < 0) {
            throw new \InvalidArgumentException('$group must be an positive integer or 0.');
        }

        $this->groups[$group][mb_strtolower($field)] = $value;

        return $this;
    }

    /**
     * Look if the field-name is legal.
     *
     * @param string $fieldname
     * @return boolean
     */
    public function isFieldname($fieldname)
    {
        return (is_string($fieldname) && preg_match('/^\p{L}[\p{L}\p{N}_]*$/iu', $fieldname) > 0);
    }
}