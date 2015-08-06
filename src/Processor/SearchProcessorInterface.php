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
 * SearchProcessorInterface.
 *
 * @author Sebastiaan Stok <s.stok@rollerscapes.net>
 */
interface SearchProcessorInterface
{
    /**
     * Processes the search data from the request.
     *
     * Returns false when there are violations, errors, or when there is no new condition.
     * You should call isValid() to determine of the result is valid.
     *
     * @param Request $request
     *
     * @return self
     */
    public function processRequest(Request $request);

    /**
     * Gets the unique filtering-code for the current condition.
     *
     * @return string
     */
    public function getSearchCode();

    /**
     * Gets the SearchCondition.
     *
     * @return SearchConditionInterface|null
     */
    public function getSearchCondition();

    /**
     * Gets the exported format of the SearchCondition.
     *
     * @param string $format
     *
     * @throws \RuntimeException When there is no SearchCondition or its invalid
     *
     * @return string|array Exported format
     */
    public function exportSearchCondition($format);

    /**
     * Gets processing error.
     *
     * @return array[]|\Traversable
     */
    public function getErrors();

    /**
     * @return ProcessorConfig
     */
    public function getConfig();

    /**
     * Returns whether the processed result is valid.
     *
     * @param bool $allowEmpty
     *
     * @return bool
     */
    public function isValid($allowEmpty = true);

    /**
     * Returns whether a new condition was submitted.
     *
     * @param bool $requireValid Require that the processed condition is valid
     *                           default is true
     *
     * @return bool
     */
    public function isSubmitted($requireValid = true);
}
