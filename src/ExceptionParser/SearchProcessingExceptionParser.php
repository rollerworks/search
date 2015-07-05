<?php

/*
 * This file is part of the RollerworksSearch package.
 *
 * (c) Sebastiaan Stok <s.stok@rollerscapes.net>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Rollerworks\Bundle\SearchBundle\ExceptionParser;

use Rollerworks\Component\ExceptionParser\ExceptionParserInterface;
use Rollerworks\Component\Search\Exception\InputProcessorException;

/**
 * The SearchProcessingExceptionParser parses "InputProcessorException".
 *
 * It's important this parser is tried last as other exceptions extend
 * from the "InputProcessorException" and this exception is not translatable.
 */
class SearchProcessingExceptionParser implements ExceptionParserInterface
{
    public function accepts(\Exception $exception)
    {
        return $exception instanceof InputProcessorException;
    }

    /**
     * @param \Exception|InputProcessorException $exception
     *
     * @return array
     */
    public function parseException(\Exception $exception)
    {
        return [
            'message' => $exception->getMessage(),
            'parameters' => [],
        ];
    }
}
