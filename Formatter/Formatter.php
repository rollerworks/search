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
use Rollerworks\RecordFilterBundle\FilterConfig;
use Rollerworks\RecordFilterBundle\FilterValuesBag;

use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Translation\TranslatorInterface;
use \InvalidArgumentException, \RuntimeException;

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
     * State of the formatter
     *
     * @var boolean
     */
    protected $formatted = false;

    /**
     * Registered values per field.
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
     * DIC container instance
     *
     * @var \Symfony\Component\DependencyInjection\ContainerInterface
     */
    protected $container;

    /**
     * @var ModifierInterface[]
     */
    protected $modifiers = array();

    /**
     * Current field-label.
     *
     * Used for exception handling
     *
     * @var string
     */
    protected $currentFieldLabel = null;

    /**
     * Constructor
     *
     * @param \Symfony\Component\Translation\TranslatorInterface $translator
     *
     * @api
     */
    public function __construct(TranslatorInterface $translator)
    {
        $this->translator = $translator;

        $this->__init();
    }

    /**
     * Returns the search-filters per group and field.
     *
     * This returns a array list of OR-groups and there fields.
     * The fields-list is associative array where the value is an \Rollerworks\RecordFilterBundle\FilterStruct object.
     *
     * @return array
     *
     * @api
     */
    public function getFilters()
    {
        if (false === $this->formatted) {
            throw new RuntimeException('Formatter::getFilters(): formatInput() must be executed first.');
        }

        return $this->finalFilters;
    }

    /**
     * Register modifier
     *
     * @param ModifierInterface $modifier
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
     * Perform the formatting of the given input.
     *
     * In case of an validation error (by modifier)
     *  this will throw an ValidationException containing the (user-friendly) error-message.
     *
     * @param \Rollerworks\RecordFilterBundle\Input\InputInterface $input
     * @return bool
     *
     * @api
     */
    public function formatInput(\Rollerworks\RecordFilterBundle\Input\InputInterface $input)
    {
        $this->formatted = false;

        $groups = $input->getGroups();

        if (empty($groups)) {
            return false;
        }

        $this->messages = array('info' => array(), 'error' => array());

        try {
            foreach ($groups as $groupIndex => $values) {
                $this->filterFormatter($input->getFieldsConfig(), $values, $groupIndex);
            }

            $this->formatted = true;
        }
        catch (ValidationException $e) {
            $params = array_merge($e->getParams(), array(
                '%label%'   => $this->currentFieldLabel,
                '%group%'   => $groupIndex + 1));

            if ($e->getMessage() === 'validation_warning' && isset($params['%msg%'])) {
                $params['%msg%'] = $this->translator->trans($params['%msg%'], $params);
            }

            $this->messages['error'][] = $this->translator->trans('record_filter.' . $e->getMessage(), $params);

            return false;
        }

        return true;
    }

    /**
     * Get the validation messages.
     *
     * Returns an array containing, error, info and warning
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
     * Init only used for the factory
     *
     * @api
     */
    protected function __init()
    {
    }

    /**
     * Add an new message to the list
     *
     * @param string  $transMessage
     * @param string  $label
     * @param integer $groupIndex
     * @param array   $params
     */
    protected function addValidationMessage($transMessage, $label, $groupIndex, $params = array())
    {
        $params = array_merge($params, array(
            '%label%' => $label,
            '%group%' => $groupIndex + 1));

        $this->messages['info'][] = $this->translator->trans('record_filter.' . $transMessage, $params);
    }

    /**
     * Perform the formatting of the given values (per group)
     *
     * @param array     $filtersConfig
     * @param array     $filters
     * @param integer   $groupIndex
     * @return bool
     */
    protected function filterFormatter(array $filtersConfig, array $filters, $groupIndex)
    {
        /** @var FilterValuesBag $filter */
        foreach ($filters as $fieldName => $filter) {
            $filterConfig = $filtersConfig[$fieldName]['config'];

            $this->currentFieldLabel = $filter->getLabel();

            /** @var ModifierInterface $modifier */
            foreach ($this->modifiers as $modifier) {
                $removeIndexes = $modifier->modFilters($this, $filterConfig, $filter, $groupIndex);

                if (null === $removeIndexes) {
                    continue;
                }

                foreach ($modifier->getMessages() as $currentMessage) {
                    if (is_array($currentMessage)) {
                        if (!isset($currentMessage['message'], $currentMessage['params'])) {
                            throw new RuntimeException('Missing either index 0 or 1.');
                        }

                        $message       = $currentMessage['message'];
                        $messageParams = $currentMessage['params'];
                    }
                    else {
                        $message       = $currentMessage;
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
