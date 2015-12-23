<?php

/*
 * This file is part of the RollerworksSearch package.
 *
 * (c) Sebastiaan Stok <s.stok@rollerscapes.net>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Rollerworks\Bundle\SearchBundle\Processor;

use Rollerworks\Component\Search\Input\ProcessorConfig;
use Rollerworks\Component\Search\SearchConditionInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * AbstractCacheSearchProcessor provides the basic logic for all processors.
 *
 * @author Sebastiaan Stok <s.stok@rollerscapes.net>
 */
abstract class AbstractSearchProcessor implements SearchProcessorInterface
{
    /**
     * @var Request
     */
    protected $request;

    /**
     * @var SearchConditionInterface
     */
    protected $searchCondition;

    /**
     * @var string
     */
    protected $searchCode = '';

    /**
     * @var string
     */
    protected $uriPrefix;

    /**
     * @var ProcessorConfig
     */
    protected $config;

    /**
     * @var array
     */
    protected $errors = [];

    /**
     * @var string
     */
    protected $formFieldPattern;

    /**
     * {@inheritdoc}
     */
    public function getSearchCode()
    {
        return $this->searchCode;
    }

    /**
     * {@inheritdoc}
     */
    public function getSearchCondition()
    {
        return $this->searchCondition;
    }

    /**
     * {@inheritdoc}
     */
    public function isValid($allowEmpty = true)
    {
        if (count($this->errors) > 0) {
            return false;
        }

        if (!$this->searchCondition) {
            return $allowEmpty;
        }

        return !$this->searchCondition->getValuesGroup()->hasErrors(true);
    }

    /**
     * {@inheritdoc}
     */
    public function isSubmitted($requireValid = true)
    {
        if (!$this->request || !$this->request->isMethod('POST')) {
            return false;
        }

        if (!$requireValid) {
            return true;
        }

        return $this->isValid();
    }

    /**
     * {@inheritdoc}
     */
    public function getErrors()
    {
        return $this->errors;
    }

    /**
     * @return ProcessorConfig
     */
    public function getConfig()
    {
        return $this->config;
    }

    /**
     * Gets a Request object value from the request.
     *
     * @param string $name    Name of the parameter
     * @param string $default Default value to use when value is missing or invalid
     * @param bool   $query   rue if the filter must be fetched from the 'query (GET),
     *                        false for 'request' (POST)
     *
     * @return string|array
     */
    protected function getRequestParam($name, $default = '', $query = true)
    {
        if (!$query) {
            return RequestUtils::getParameterBagValue($this->request->request, sprintf($this->formFieldPattern, $name), $default);
        }

        if ($this->uriPrefix) {
            $name = $this->uriPrefix.'['.$name.']';
        }

        $value = RequestUtils::getParameterBagValue($this->request->query, $name, $default);

        if (!is_string($value)) {
            $value = $default;
        }

        return $value;
    }
}
