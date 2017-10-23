<?php

declare(strict_types=1);

/*
 * This file is part of the RollerworksSearch package.
 *
 * (c) Sebastiaan Stok <s.stok@rollerscapes.net>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Rollerworks\Component\Search\Elasticsearch;

/**
 * Class QueryConversionHints.
 */
class QueryConversionHints
{
    public const CONTEXT_SIMPLE_VALUES = 'SIMPLE_VALUES';
    public const CONTEXT_EXCLUDED_SIMPLE_VALUES = 'EXCLUDED_SIMPLE_VALUES';

    /** @var bool */
    public $identifier = false;

    /**
     * @var string One of ConversionHints::CONTEXT_* constants
     */
    public $context;
}
