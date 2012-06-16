<?php

/**
 * This file is part of the RollerworksRecordFilterBundle.
 *
 * (c) Sebastiaan Stok <s.stok@rollerscapes.net>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Rollerworks\RecordFilterBundle\Formatter;

use Rollerworks\RecordFilterBundle\Formatter\Modifier\ModifierInterface;
use Rollerworks\RecordFilterBundle\MessageBag;
use Rollerworks\RecordFilterBundle\Value\FilterValuesBag;
use Rollerworks\RecordFilterBundle\Input\InputInterface;
use Rollerworks\RecordFilterBundle\FieldSet;
use Symfony\Component\Translation\TranslatorInterface;

/**
 * Formats the filters by performing the registered modifiers.
 *
 * @author Sebastiaan Stok <s.stok@rollerscapes.net>
 */
class Formatter implements FormatterInterface
{
    /**
     * @var MessageBag
     */
    protected $messageBag = null;

    /**
     * @var boolean
     */
    protected $formatted = false;

    /**
     * Final filtering values.
     *
     * Each entry is an [group-id][field-name] => (FilterStruct object)
     *
     * @var array
     */
    protected $finalFilters = array();

    /**
     * @var \Symfony\Component\Translation\TranslatorInterface
     */
    protected $translator;

    /**
     * @var ModifierInterface[]
     */
    protected $modifiers = array();

    /**
     * @var \Rollerworks\RecordFilterBundle\FieldSet|null
     */
    protected $fieldSet;

    /**
     * Constructor.
     *
     * @param TranslatorInterface $translator
     *
     * @api
     */
    public function __construct(TranslatorInterface $translator)
    {
        $this->translator = $translator;
    }

    /**
     * {@inheritdoc}
     */
    public function getFilters()
    {
        if (false === $this->formatted) {
            throw new \RuntimeException('formatInput() must be executed before calling this function.');
        }

        return $this->finalFilters;
    }

    /**
     * {@inheritdoc}
     */
    public function getFieldSet()
    {
        return $this->fieldSet;
    }

    /**
     * Registers an modifier.
     *
     * @param ModifierInterface $modifier
     *
     * @return Formatter
     *
     * @api
     */
    public function registerModifier(ModifierInterface $modifier)
    {
        $this->modifiers[ $modifier->getModifierName() ] = $modifier;

        return $this;
    }

    /**
     * Formats the input, by performing all the registered modifiers.
     *
     * @param InputInterface $input
     *
     * @return boolean
     *
     * @api
     */
    public function formatInput(InputInterface $input)
    {
        $this->formatted  = false;
        $this->fieldSet   = $input->getFieldSet();
        $this->messageBag = new MessageBag($this->translator);

        $groups = $input->getGroups();

        if (false === $groups || empty($groups)) {
            return false;
        }

        foreach ($groups as $groupIndex => $values) {
            $this->formatGroup($input->getFieldSet(), $values, $groupIndex);
        }

        $this->formatted = count($this->messageBag->get('error')) < 1;

        return $this->formatted;
    }

    /**
     * Get the formatter messages.
     *
     * Returns an array containing: error and info
     *
     * @return array
     *
     * @throws \RuntimeException
     *
     * @api
     */
    public function getMessages()
    {
        if (null === $this->messageBag) {
            throw new \RuntimeException('formatInput() must be executed before calling this function.');
        }

        return $this->messageBag->all();
    }

    /**
     * Perform the formatting of the given values (per group).
     *
     * @param FieldSet $filtersConfig
     * @param array    $filters
     * @param integer  $groupIndex
     *
     * @return boolean
     */
    protected function formatGroup(FieldSet $filtersConfig, array $filters, $groupIndex)
    {
        /** @var FilterValuesBag $filter */
        foreach ($filters as $fieldName => $filter) {
            $filterConfig = $filtersConfig->get($fieldName);

            $this->messageBag->setTranslatorParams(array(
                '{{ label }}' => $filterConfig->getLabel(),
                '{{ group }}' => $groupIndex + 1)
            );

            foreach ($this->modifiers as $modifier) {
                $modifierResult = $modifier->modFilters($this, $this->messageBag, $filterConfig, $filter, $groupIndex);

                if (false === $modifierResult) {
                    break;
                }

                if (null === $modifierResult) {
                    continue 1;
                }
            }

            $this->finalFilters[$groupIndex][$fieldName] = $filter;
        }
    }
}
