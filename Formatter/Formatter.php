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
use Rollerworks\RecordFilterBundle\Exception\ValidationException;
use Rollerworks\RecordFilterBundle\Input\InputInterface;
use Rollerworks\RecordFilterBundle\FieldSet;
use Rollerworks\RecordFilterBundle\Value\FilterValuesBag;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Translation\TranslatorInterface;

/**
 * Format the filters by performing the registered modifiers.
 *
 * @author Sebastiaan Stok <s.stok@rollerscapes.net>
 */
class Formatter implements FormatterInterface
{
    /**
     * @var array
     */
    protected $messages = array('info' => array(), 'error' => array());

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
     * Translator instance
     *
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
     * Current field-label.
     *
     * Used for exception handling
     *
     * @var string
     */
    protected $currentFieldLabel = null;

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
            throw new \RuntimeException('Formatter::getFilters(): formatInput() must be executed first.');
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
     * Registers an modifier
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
        $this->formatted = false;
        $this->fieldSet = $input->getFieldsConfig();

        $groups = $input->getGroups();

        if (empty($groups)) {
            return false;
        }

        $this->messages = array('info' => array(), 'error' => array());

        try {
            foreach ($groups as $groupIndex => $values) {
                $this->formatGroup($input->getFieldsConfig(), $values, $groupIndex);
            }

            $this->formatted = true;

            return true;
        } catch (ValidationException $e) {
            $params = array_merge($e->getParams(), array(
                '%label%' => $this->currentFieldLabel,
                '%group%' => $groupIndex + 1));

            if ($e->getMessage() === 'validation_warning' && isset($params['%msg%'])) {
                $params['%msg%'] = $this->translator->trans($params['%msg%'], $params);
            }

            $this->messages['error'][] = $this->translator->trans($e->getMessage(), $params);

            return false;
        }
    }

    /**
     * Get the formatter messages.
     *
     * Returns an array containing:, error, info and warning
     *
     * @return array
     *
     * @api
     */
    public function getMessages()
    {
        return $this->messages;
    }

    /**
     * Add an new message to the list
     *
     * @param string  $transMessage
     * @param string  $label
     * @param integer $groupIndex
     * @param array   $params
     */
    protected function addMessage($transMessage, $label, $groupIndex, $params = array())
    {
        $params = array_merge($params, array(
            '%label%' => $label,
            '%group%' => $groupIndex + 1));

        $this->messages['info'][] = $this->translator->trans('record_filter.' . $transMessage, $params);
    }

    /**
     * Perform the formatting of the given values (per group)
     *
     * @param FieldSet $filtersConfig
     * @param array    $filters
     * @param integer  $groupIndex
     *
     * @return boolean
     *
     * @throws \RuntimeException
     */
    protected function formatGroup(FieldSet $filtersConfig, array $filters, $groupIndex)
    {
        /** @var FilterValuesBag $filter */
        foreach ($filters as $fieldName => $filter) {
            $filterConfig = $filtersConfig->get($fieldName);
            $this->currentFieldLabel = $filterConfig->getLabel();

            foreach ($this->modifiers as $modifier) {
                $removeIndexes = $modifier->modFilters($this, $filterConfig, $filter, $groupIndex);

                if (null === $removeIndexes) {
                    continue;
                }

                foreach ($modifier->getMessages() as $currentMessage) {
                    if (is_array($currentMessage)) {
                        if (!isset($currentMessage['message'], $currentMessage['params'])) {
                            throw new \RuntimeException('Missing either index message or params.');
                        }

                        $message = $currentMessage['message'];
                        $messageParams = $currentMessage['params'];
                    } else {
                        $message = $currentMessage;
                        $messageParams = array();
                    }

                    $messageParams = array_merge($messageParams, array('%label%' => $this->currentFieldLabel, '%group%' => $groupIndex + 1));
                    $this->messages['info'][] = $this->translator->trans('record_filter.' . $message, $messageParams);
                }
            }

            $this->finalFilters[$groupIndex][$fieldName] = $filter;
        }
    }
}
