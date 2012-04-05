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

use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Translation\TranslatorInterface;

use Rollerworks\RecordFilterBundle\Exception\ReqFilterException;
use Rollerworks\RecordFilterBundle\Exception\ValidationException;
use Rollerworks\RecordFilterBundle\Formatter\Modifier\PreModifierInterface;
use Rollerworks\RecordFilterBundle\Formatter\Modifier\PostModifierInterface;
use Rollerworks\RecordFilterBundle\ValueMatcherInterface;
use Rollerworks\RecordFilterBundle\FilterConfig;
use Rollerworks\RecordFilterBundle\FilterTypeInterface;
use Rollerworks\RecordFilterBundle\Value\Compare;
use Rollerworks\RecordFilterBundle\Value\Range;
use Rollerworks\RecordFilterBundle\Value\SingleValue;
use Rollerworks\RecordFilterBundle\FilterValuesBag;

use \InvalidArgumentException, \RuntimeException;

/**
 * Validate and format the input values and fields.
 *
 * @author Sebastiaan Stok <s.stok@rollerscapes.net>
 */
class Formatter implements FormatterInterface
{
    /**
     * Validation messages
     *
     * @var array
     */
    protected $messages = array('info' => array(), 'error' => array());

    /**
     * Field aliases.
     * An alias is kept as: alias => destination
     *
     * @var array
     */
    protected $fieldsAliases = array();

    /**
     * Optional field alias using the translator.
     * Beginning with this prefix.
     *
     * @var string
     */
    protected $aliasTranslatorPrefix;

    /**
     * Optional field alias using the translator.
     * Domain to search in.
     *
     * @var string
     */
    protected $aliasTranslatorDomain = 'filter';

    /**
     * Registered validations per field.
     *
     * Field-name => (FilterConfig object)
     *
     * @var array
     */
    protected $filtersConfig = array();

    /**
     * Registered values per field.
     * Each entry is an [group-id][field-name] => (FilterStruct object)
     *
     * @var array
     */
    protected $finalFilters = array();

    /**
     * Registered optimized values per field.
     * Each entry is an [group-id][field-label] => (Values string)
     *
     * The duplicates, redundant and overlapping values have been removed.
     *
     * @var array
     */
    protected $optimizedFilters = array();

    /**
     * State of the formatter
     *
     * @var bool
     */
    protected $isFormatted = false;

    /**
     * Does the value list contain OR-groups
     *
     * @var bool
     */
    protected $hasGroups = false;

    /**
     * FilterQuery representation of the final filters
     *
     * @var string
     */
    protected $filterQuery = null;

    /**
     * FilterQuery representation of the optimized filters
     *
     * @var string
     */
    protected $optimizedFilterQuery = null;

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
     * @var ModifiersRegistry
     */
    protected $modifiersRegistry = null;

    /**
     * Current field.
     * Used for exception handling
     *
     * @var string
     */
    protected $currentFieldName = null;

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
     * Set the DIC container for types that need it
     *
     * @param \Symfony\Component\DependencyInjection\ContainerInterface $container
     *
     * @api
     */
    public function setContainer(ContainerInterface $container = null)
    {
        $this->container = $container;
    }

    /**
     * Set an ModifiersRegistry instance
     *
     * @param ModifiersRegistry $registry
     *
     * @api
     */
    public function setModifiersRegistry(ModifiersRegistry $registry)
    {
        $this->modifiersRegistry = $registry;
    }

    /**
     * Returns whether is an modifier instance registered.
     *
     * @return bool
     *
     * @api
     */
    public function hasModifiersRegistry()
    {
        return null !== $this->modifiersRegistry;
    }

    /**
     * Returns the current ModifiersRegistry instance.
     *
     * When there is no instance, its created depending on $createWhenEmpty.
     * Else an RuntimeException gets thrown.
     *
     * @throws \RuntimeException
     *
     * @param bool $createWhenEmpty
     * @return ModifiersRegistry
     *
     * @api
     */
    public function getModifiersRegistry($createWhenEmpty = false)
    {
        if ($createWhenEmpty && null === $this->modifiersRegistry) {
            $this->modifiersRegistry = new ModifiersRegistry();
        }
        elseif (null === $this->modifiersRegistry) {
            throw new RuntimeException('No ModifiersRegistry instance registered.');
        }

        return $this->modifiersRegistry;
    }

    /**
     * Set the field alias by translator beginning with this prefix.
     *
     * Example: product.alias.[label]
     * Where [label] is the actual fieldname as input.
     *
     * @param string $pathPrefix    This prefix is added before every search, like filters.labels.
     * @param string $domain        Default is filter
     * @return Formatter
     */
    public function setFieldAliasByTranslator($pathPrefix, $domain = 'filter')
    {
        if (!is_string($pathPrefix)) {
            throw new InvalidArgumentException('Prefix must be an string and can not be empty');
        }

        if (!is_string($domain)) {
            throw new InvalidArgumentException('Domain must be an string and can not be empty');
        }

        $this->aliasTranslatorPrefix = $pathPrefix;
        $this->aliasTranslatorDomain = $domain;

        return $this;
    }

    /**
     * Set one or more alias(es) of an field.
     *
     * Existing aliases will be overwritten.
     *
     * @param string        $fieldName Original field-name
     * @param string|array  $aliases
     * @return Formatter
     */
    public function setFieldAlias($fieldName, $aliases)
    {
        $this->isFormatted = false;

        if (is_array($aliases)) {
            foreach ($aliases as $alias) {
                $this->fieldsAliases[ $alias ] = $fieldName;
            }
        }
        elseif (is_string($aliases)) {
            $this->fieldsAliases[ $aliases ] = $fieldName;
        }

        return $this;
    }

    /**
     * Set the configuration of an (new) filter-name.
     *
     * The configuration can be used by all modifiers.
     *
     * @param string            $fieldName          Converted to lower-case
     * @param null|FilterTypeInterface   $valueType          Optional filter-type
     * @param boolean           $required           Whether the field must have an value (default is false)
     * @param boolean           $acceptRanges       Whether ranges are accepted (default is false)
     * @param boolean           $acceptCompares     Whether comparisons are accepted (default is false)
     * @return Formatter
     *
     * @api
     */
    public function setField($fieldName, FilterTypeInterface $valueType = null, $required = false, $acceptRanges = false, $acceptCompares = false)
    {
        if (!is_bool($acceptRanges)) {
            throw (new InvalidArgumentException('Formatter::setField(): $acceptRanges must be an boolean'));
        }
        elseif (!is_bool($acceptCompares)) {
            throw (new InvalidArgumentException('Formatter::setField(): $acceptCompares must be an boolean'));
        }
        elseif (!is_bool($required)) {
            throw (new InvalidArgumentException('Formatter::setField(): $required must be an boolean'));
        }

        $this->isFormatted = false;

        if (!empty($valueType) && $valueType instanceof ContainerAwareInterface) {
            /** @var ContainerAwareInterface $valueType */
            $valueType->setContainer($this->container);
        }

        $this->filtersConfig[ mb_strtolower($fieldName) ] = new FilterConfig($valueType, $required, $acceptRanges, $acceptCompares);

        return $this;
    }

    /**
     * Returns an associate array with all the registered filters and there configuration.
     *
     * @return array
     *
     * @api
     */
    public function getFields()
    {
        return $this->filtersConfig;
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
        if (empty($this->filtersConfig)) {
            throw new RuntimeException('Formatter::getFilters(): No fields are registered.');
        }

        if (false === $this->isFormatted) {
            throw new RuntimeException('Formatter::getFilters(): formatInput() must be executed first.');
        }

        return $this->finalFilters;
    }

    /**
     * Returns the search-filters optimized-values per group and field.
     *
     * This returns a array list of OR-groups and there fields.
     * The fields-list is associative array where the value is an string with all the values.
     *
     * @param bool $returnQuery
     * @param bool $fieldPerLine     Return each field on a new line (only when $returnQuery is true)
     * @return array|string
     *
     * @api
     */
    public function getFiltersValues($returnQuery = false, $fieldPerLine = false)
    {
        if (empty($this->filtersConfig)) {
            throw new RuntimeException('ValidationFormatter::getFilters(): No fields/validations are registered.');
        }

        if (false === $this->isFormatted) {
            throw new RuntimeException('ValidationFormatter::getFilters(): formatInput() must be executed first and must have returned true.');
        }

        if ($returnQuery && (($fieldPerLine && !strpos($this->optimizedFilterQuery, \PHP_EOL)) || (!$fieldPerLine && strpos($this->optimizedFilterQuery, \PHP_EOL)))) {
            $this->optimizedFilterQuery = null;
        }

        if ($returnQuery && $this->optimizedFilterQuery === null) {
            foreach ($this->optimizedFilters as $groupFilter) {
                $this->optimizedFilterQuery .= '( ';

                foreach ($groupFilter as $label => $filterValues) {
                    $this->optimizedFilterQuery .= $label . '=' . implode(', ', $filterValues) . '; ';

                    if ($fieldPerLine) {
                        $this->optimizedFilterQuery = rtrim($this->optimizedFilterQuery);
                        $this->optimizedFilterQuery .= PHP_EOL;
                    }
                }

                $this->optimizedFilterQuery = rtrim($this->optimizedFilterQuery) . ' ), ';
            }

            $this->optimizedFilterQuery = rtrim($this->optimizedFilterQuery, ', ');

            if (!$this->hasGroups) {
                $this->optimizedFilterQuery = substr($this->optimizedFilterQuery, 2, -2);
            }

            $this->optimizedFilterQuery = rtrim($this->optimizedFilterQuery);
        }

        if ($returnQuery) {
            return $this->optimizedFilterQuery;
        }
        else {
            return $this->optimizedFilters;
        }
    }

    /**
     * Register an pre-modifier instance.
     *
     * @param PreModifierInterface $modifier
     * @return Formatter
     *
     * @api
     */
    public function registerPreModifier(PreModifierInterface $modifier)
    {
        $this->getModifiersRegistry(true)->registerPreModifier($modifier);

        return $this;
    }

    /**
     * Register an pre-modifier instance.
     *
     * @param PostModifierInterface $modifier
     * @return Formatter
     *
     * @api
     */
    public function registerPostModifier(PostModifierInterface $modifier)
    {
        $this->getModifiersRegistry(true)->registerPostModifier($modifier);

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
        if (empty($this->filtersConfig)) {
            throw new RuntimeException('Formatter::formatInput(): No fields registered.');
        }

        $values = $input->getValues();

        $this->isFormatted = false;

        if (empty($values)) {
            return false;
        }

        $this->hasGroups = $input->hasGroups();

        $this->optimizedFilterQuery = null;
        $this->messages             = array('info' => array(), 'error' => array());

        try {
            foreach ($values as $groupIndex => $groupValues) {
                if (empty($groupValues)) {
                    continue;
                }

                $this->filterFormatter($groupValues, $groupIndex);
            }

            $this->isFormatted = true;
        }
        catch (ValidationException $e) {
            $params = array_merge($e->getParams(), array(
                '%field%'   => $this->currentFieldName,
                '%group%'   => $groupIndex + 1));

            if ($e->getMessage() === 'validation_warning' && isset($params['%msg%'])) {
                $params['%msg%'] = $this->translator->transChoice($params['%msg%'], $this->hasGroups ? 1 : 0, $params);
            }

            $this->messages['error'][] = $this->translator->transChoice('record_filter.' . $e->getMessage(), $this->hasGroups ? 1 : 0, $params);

            return false;
        }

        return true;
    }

    /**
     * Returns whether the value-list has or-groups.
     *
     * @return boolean
     *
     * @api
     */
    public function hasGroups()
    {
        return $this->hasGroups;
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
     * Get the formatted values as an QueryFilter-string.
     *
     * @return string
     *
     * @api
     */
    public function __toString()
    {
        if ($this->isFormatted === false) {
            return null;
        }
        elseif (!is_null($this->filterQuery)) {
            return $this->filterQuery;
        }

        /**
         * @var \Rollerworks\RecordFilterBundle\ValuesBag $filter
         */
        if ($this->hasGroups) {
            foreach ($this->finalFilters as $groupFilters) {
                $this->filterQuery .= '( ';

                foreach ($groupFilters as $filter) {
                    $this->filterQuery .= $filter->getLabel() . '=';
                    $this->filterQuery .= $filter->getOriginalInput();
                    $this->filterQuery .= '; ';
                }

                $this->filterQuery = rtrim($this->filterQuery) . ' ), ';
            }

            $this->filterQuery = rtrim($this->filterQuery, ', ');
        }
        else {
            foreach ($this->finalFilters[0] as $filter) {
                $this->filterQuery .= $filter->getLabel() . '=';
                $this->filterQuery .= $filter->getOriginalInput();
                $this->filterQuery .= '; ';
            }
        }

        $this->filterQuery = rtrim($this->filterQuery);

        return $this->filterQuery;
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
     * @param string  $fieldName
     * @param integer $groupIndex
     * @param array   $params
     */
    protected function addValidationMessage($transMessage, $fieldName, $groupIndex, $params = array())
    {
        $params = array_merge($params, array(
            '%field%' => $fieldName,
            '%group%' => $groupIndex + 1)
        );

        $this->messages['info'][] = $this->translator->transChoice('record_filter.' . $transMessage, $this->hasGroups ? 1 : 0, $params);
    }

    /**
     * Perform the formatting of the given values (per group)
     *
     * @param array     $values
     * @param integer   $groupIndex
     * @return bool
     */
    protected function filterFormatter($values, $groupIndex)
    {
        $originalLabels = array();

        if (!empty($this->fieldsAliases) || null !== $this->aliasTranslatorPrefix) {
            $originalLabels = $this->handleAlias($values, $groupIndex);
        }

        if (null === $this->modifiersRegistry) {
            $this->modifiersRegistry = new ModifiersRegistry();
        }

        /** @var \Rollerworks\RecordFilterBundle\Formatter\Modifier\PreModifierInterface $modifier */
        foreach ($this->modifiersRegistry->getPreModifiers() as $modifier) {
            $values = $modifier->modFilters($this, $values, $groupIndex);
        }

        foreach ($this->filtersConfig as $fieldName => $filterConfig) {
            $this->currentFieldName = $fieldName;

            $ranges         = array();
            $excludedRanges = array();
            $excludesValues = array();
            $compares       = array();
            $singleValues   = array();

            $valueMatcherRegex       = '';
            $valueMatcherRegexSingle = '';

            /** @var \Rollerworks\RecordFilterBundle\Formatter\Validation\FilterConfig $filterConfig */
            if ($filterConfig->isRequired() && !isset($values[ $fieldName ])) {
                throw new ReqFilterException();
            }
            elseif (!isset($values[ $fieldName ])) {
                continue;
            }

            if (isset($originalLabels[ $fieldName ])) {
                $label = $originalLabels[ $fieldName ];
            }
            else {
                $label = $fieldName;
            }

            if ($filterConfig->hasType() && ($filterConfig->getType() instanceof ValueMatcherInterface)) {
                $_sRegex                 = $filterConfig->getType()->getRegex();

                $valueMatcherRegex       = '|' . $_sRegex . '-' . $_sRegex . '|(?:>=|<=|<>|[<>!])?' . $_sRegex;
                $valueMatcherRegexSingle = '|' . $_sRegex;
            }

            if (preg_match_all('#\s*("(?:(?:[^"]+|"")+)"'.$valueMatcherRegex.'|[^,]+)\s*(,\s*|$)#ius', $values[ $fieldName ], $filterValues)) {
                foreach ($filterValues[1] as $valueIndex => $currentValue) {
                    $value = null;

                    // Comparison
                    if (preg_match('#^(>=|<=|<>|[<>])("(?:(?:[^"]+|"")+)"'.$valueMatcherRegexSingle.'|[^\h]+)$#us', $currentValue, $comparisonValue)) {
                        if (!$filterConfig->acceptCompares()) {
                            throw new ValidationException('no_compare_support');
                        }

                        $compares[ $valueIndex ] = new Compare(self::fixQuotes($comparisonValue[2]), $comparisonValue[1]);
                    }
                    // Ranges and single (exclude)
                    else {
                        $isExclude = false;

                        if ('!' === mb_substr(trim($currentValue), 0, 1)) {
                            $isExclude      = true;
                            $currentValue   = mb_substr(ltrim($currentValue), 1);
                        }

                        if (false !== strpos($currentValue, '-' )) {
                            // Value starts with an quote, check if its range and not an quoted value with a '-' in it.
                            if ('"' === mb_substr(trim($currentValue), 0, 1)) {
                                // Both quoted
                                if (preg_match('#^("(?:(?:[^"]+|"")+)")-("(?:(?:[^"]+|"")+)")$#s', $currentValue, $rangeValue)) {
                                    $value = new Range(self::fixQuotes($rangeValue[1]), self::fixQuotes($rangeValue[2]));
                                }
                                // Only first quoted
                                elseif (preg_match('#^("(?:(?:[^"]+|"")+)")-([^\s]+)$#s', $currentValue, $rangeValue)) {
                                    $value = new Range(self::fixQuotes($rangeValue[1]), $rangeValue[2]);
                                }
                            }
                            // By value matcher
                            elseif (!empty($valueMatcherRegexSingle)) {
                                if (preg_match('#^('.substr($valueMatcherRegexSingle, 1).')-('.substr($valueMatcherRegexSingle, 1).')$#uis', $currentValue, $rangeValue)) {
                                    $value = new Range($rangeValue[1], $rangeValue[2]);
                                }
                                // Remember to check for an positive singe-value match
                                elseif (!preg_match('#^('.substr($valueMatcherRegexSingle, 1).')$#uis', $currentValue, $rangeValue) && preg_match('#^([^-]+)-([^\s]+)$#s', $currentValue, $rangeValue)) {
                                    $value = new Range($rangeValue[1], $rangeValue[2]);
                                }
                            }
                            // None quoted/only right quoted
                            elseif (preg_match('#^([^-]+)-([^\s]+)$#s', $currentValue, $rangeValue)) {
                                $value = new Range($rangeValue[1], self::fixQuotes($rangeValue[2]));
                            }
                        }

                        if (null !== $value) {
                            if (!$filterConfig->acceptRanges()) {
                                throw new ValidationException('no_range_support');
                            }

                            if ($isExclude) {
                                $excludedRanges[ $valueIndex ] = $value;
                            }
                            else {
                                $ranges[ $valueIndex ] = $value;
                            }
                        }
                        // Single (exclude) value
                        else {
                            $value = new SingleValue(self::fixQuotes($currentValue));

                            if ($isExclude) {
                                $excludesValues[ $valueIndex ] = $value;
                            }
                            else {
                                $singleValues[ $valueIndex ] = $value;
                            }
                        }
                    }
                }
            }
            elseif (true === $filterConfig->isRequired()) {
                throw new ReqFilterException();
            }
            else {
                $this->addValidationMessage('parse_error', $fieldName, $groupIndex);
                continue;
            }

            $fieldStruct = new FilterValuesBag($label, $values[ $fieldName ], $singleValues, $excludesValues, $ranges, $compares, $excludedRanges, $valueIndex);

            /** @var \Rollerworks\RecordFilterBundle\Formatter\PostModifierInterface $modifier */
            foreach ($this->modifiersRegistry->getPostModifiers() as $modifier) {
                $removeIndexes = $modifier->modFilters($this, $filterConfig, $fieldStruct, $groupIndex);

                if (null === $removeIndexes) {
                    $fieldStruct = null;
                }
                elseif (is_array($removeIndexes)) {
                    // Remove values that were marked as deleted
                    foreach ($removeIndexes as $valIndex) {
                        unset($filterValues[1][$valIndex]);
                    }
                }

                foreach ($modifier->getMessages() as $currentMessage) {
                    if (is_array($currentMessage)) {
                        if (!isset($currentMessage[0], $currentMessage[1])) {
                            throw new RuntimeException('Missing either index 0 or 1.');
                        }

                        $message       = $currentMessage[0];
                        $messageParams = $currentMessage[1];
                    }
                    else {
                        $message       = $currentMessage;
                        $messageParams = array();
                    }

                    $messageParams = array_merge($messageParams, array(
                        '%field%'   => $this->currentFieldName,
                        '%group%'   => $groupIndex + 1)
                    );

                    $this->messages['info'][] = $this->translator->transChoice('record_filter.' . $message, $this->hasGroups ? 1 : 0, $messageParams);
                }
            }

            if (!empty($fieldStruct)) {
                $this->finalFilters[ $groupIndex ][ $fieldName ]     = $fieldStruct;
                $this->optimizedFilters[ $groupIndex ][ $fieldName ] = $filterValues[1];
            }
        }
    }

    /**
     * Remove and normalise quoted-values
     *
     * @param string $input
     * @return string
     *
     * @api
     */
    protected static function fixQuotes($input)
    {
        $input = trim($input);

        if ('"' === mb_substr($input, 0, 1)) {
            $input = mb_substr($input, 1, -1);
            $input = str_replace('""', '"', $input);
        }

        return $input;
    }

    /**
     * Handle the aliases
     *
     * @param array   $values
     * @param integer $groupIndex
     * @return array
     */
    protected function handleAlias(&$values, $groupIndex)
    {
        $originalLabels = array();

        foreach ($values as $fieldName => $value) {
            if (isset($this->filtersConfig[$fieldName])) {
                continue;
            }

            $finalFieldName = '';

            if (isset($this->fieldsAliases[$fieldName])) {
                $finalFieldName = $this->fieldsAliases[$fieldName];
            }
            elseif ($this->aliasTranslatorPrefix !== null) {
                $finalFieldName = $this->translator->trans($this->aliasTranslatorPrefix . $fieldName, array(), $this->aliasTranslatorDomain);

                if ($finalFieldName === $this->aliasTranslatorPrefix . $fieldName) {
                    $finalFieldName = null ;
                }
            }

            if (!empty($finalFieldName)) {
                if (!isset($originalLabels[ $finalFieldName ])) {
                    $originalLabels[ $finalFieldName ] = $fieldName;
                }
                else {
                    $this->addValidationMessage('merged', $fieldName, $groupIndex, array('%destination%' => $originalLabels[ $finalFieldName ]));
                }

                if (!isset($values[ $finalFieldName ])) {
                    $values[ $finalFieldName ] = $values[ $fieldName ];
                }
                else {
                    $values[ $finalFieldName ] .= ',' . $values[ $fieldName ];
                }

                unset($values[ $fieldName ]);
            }
        }

        return $originalLabels;
    }
}
