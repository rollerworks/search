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
     * Returns whether the processed result is valid.
     *
     * @return bool
     */
    public function isValid()
    {
        if (count($this->errors) > 0) {
            return false;
        }

        if (!$this->searchCondition) {
            return false;
        }

        return !$this->searchCondition->getValuesGroup()->hasErrors();
    }

    /**
     * {@inheritdoc}
     */
    public function getErrors()
    {
        return $this->errors;
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
            return $this->request->request->get(sprintf($this->formFieldPattern, $name), $default, true);
        }

        $params = $this->request->query;

        if ($this->uriPrefix) {
            $value = $params->get($this->uriPrefix.'['.$name.']', $default, true);
        } else {
            $value = $params->get($name, $default);
        }

        if (!is_string($value)) {
            $value = $default;
        }

        return $value;
    }
}
