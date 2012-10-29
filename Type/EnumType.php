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

use Symfony\Component\Translation\TranslatorInterface;
use Rollerworks\Bundle\RecordFilterBundle\Formatter\OptimizableInterface;
use Rollerworks\Bundle\RecordFilterBundle\Value\FilterValuesBag;
use Rollerworks\Bundle\RecordFilterBundle\MessageBag;

/**
 * Enum filter-type is limited to a fixed set of pre-configured values.
 *
 * Values are configured as value => label
 * And the label can be optionally translated.
 *
 * @author Sebastiaan Stok <s.stok@rollerscapes.net>
 */
class EnumType implements FilterTypeInterface, ValueMatcherInterface, OptimizableInterface
{
    private $labelToValue = array();
    private $valueToLabel = array();
    private $match;

    /**
     * @var array
     */
    protected $acceptedValues = array();

    /**
     * Constructor.
     *
     * @param array               $acceptedValues
     * @param TranslatorInterface $translator
     * @param string|null         $translatorDomain
     */
    public function __construct(array $acceptedValues, TranslatorInterface $translator = null, $translatorDomain = null)
    {
        foreach ($acceptedValues as $value => $label) {
            if (null !== $translator) {
                $label = $translator->trans($label, array(), $translatorDomain);
            }

            if (function_exists('mb_strtolower')) {
                $labelLower = mb_strtolower($label);
            } else {
                $labelLower = strtolower($label);
            }

            $this->valueToLabel[$value] = $label;
            $this->labelToValue[$labelLower] = $value;
        }
    }

    /**
     * {@inheritDoc}
     */
    public function sanitizeString($value)
    {
        if (function_exists('mb_strtolower')) {
            $value = mb_strtolower($value);
        } else {
            $value = strtolower($value);
        }

        if (isset($this->labelToValue[$value])) {
            return $this->labelToValue[$value];
        }

        return $value;
    }

    /**
     * {@inheritDoc}
     */
    public function formatOutput($value)
    {
        return isset($this->valueToLabel[$value]) ? $this->valueToLabel[$value] : $value;
    }

    /**
     * {@inheritDoc}
     */
    public function dumpValue($value)
    {
        return $value;
    }

    /**
     * Not used.
     */
    public function isHigher($input, $nextValue)
    {
        return false;
    }

    /**
     * Not used.
     */
    public function isLower($input, $nextValue)
    {
        return true;
    }

    /**
     * {@inheritDoc}
     */
    public function isEqual($input, $nextValue)
    {
        return ($input === $nextValue);
    }

    /**
     * {@inheritDoc}
     */
    public function validateValue($value, &$message = null, MessageBag $messageBag = null)
    {
        $message = null;

        if (function_exists('mb_strtolower')) {
            $value = mb_strtolower($value);
        } else {
            $value = strtolower($value);
        }

        if (!isset($this->labelToValue[$value])) {
            $messageBag->addError('enum_value_unknown', array(
                '{{ value }}'  => $value,
                '{{ values }}' => implode(', ', array_values($this->valueToLabel)))
            );

            return false;
        }

        return true;
    }

    /**
     * {@inheritDoc}
     */
    public function getMatcherRegex()
    {
        if (null === $this->match) {
            $labels = array();

            foreach ($this->valueToLabel as $type) {
                $labels[] = preg_quote($type, '#');
            }

            $this->match = sprintf('(?:%s)', implode('|', $labels));
        }

        return $this->match;
    }

    /**
     * {@inheritDoc}
     */
    public function optimizeField(FilterValuesBag $field, MessageBag $messageBag)
    {
        // All possible values are used so remove it
        if (count($field->getSingleValues()) === count($this->valueToLabel)) {
            $messageBag->addInfo('enum_value_redundant');

            return false;
        }

        return null;
    }
}
