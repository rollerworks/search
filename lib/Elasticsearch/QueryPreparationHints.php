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

class QueryPreparationHints
{
    public const CONTEXT_PRECONDITION_VALUE = 'PRECONDITION_VALUE';
    public const CONTEXT_PRECONDITION_QUERY = 'PRECONDITION_QUERY';
    public const CONTEXT_SIMPLE_VALUES = 'SIMPLE_VALUES';
    public const CONTEXT_EXCLUDED_SIMPLE_VALUES = 'EXCLUDED_SIMPLE_VALUES';
    public const CONTEXT_RANGE_VALUES = 'RANGE_VALUES';
    public const CONTEXT_EXCLUDED_RANGE_VALUES = 'EXCLUDED_RANGE_VALUES';
    public const CONTEXT_COMPARISON = 'COMPARISON';
    public const CONTEXT_PATTERN_MATCH = 'PATTERN_MATCH';
    public const CONTEXT_ORDER = 'ORDER';

    /** @var bool */
    public $identifier = false;

    /**
     * @var string Preparation context, one of ConversionHints::CONTEXT_* constants
     */
    public $context;
}
