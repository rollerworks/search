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

namespace Rollerworks\Component\Search\Processor;

use Rollerworks\Component\Search\ConditionErrorMessage;
use Rollerworks\Component\Search\SearchCondition;

final class SearchPayload
{
    /**
     * READ-ONLY: The final SearchCondition.
     *
     * @var SearchCondition|array|null Array is only used during serialization
     */
    public $searchCondition;

    /**
     * READ-ONLY: The error messages produced by the input processor.
     *
     * @var ConditionErrorMessage[]
     */
    public $messages = [];

    /**
     * READ-ONLY: The search-code used to transport the SearchCondition
     * with the URI.
     *
     * This contains the JSON exported SearchCondition, compressed
     * and converted to base64.
     *
     * @var string
     */
    public $searchCode;

    /**
     * READ-ONLY: The SearchCondition in exported format.
     *
     * @var mixed
     */
    public $exportedCondition;

    /**
     * READ-ONLY: The Format in which the condition is exported.
     *
     * @var string
     */
    public $exportedFormat;

    /**
     * READ-ONLY: Indicates whether the condition has changed in comparison
     * to the previous state.
     *
     * Don't access this property directly, use `isChanged()` instead.
     *
     * @var bool
     */
    public $changed;

    public function __construct(bool $changed = false)
    {
        $this->changed = $changed;
    }

    /**
     * Returns whether the processed request was valid (no errors).
     *
     * Note: An empty SearchCondition is not considered an error.
     * If this method returns false the input should be corrected.
     *
     * @return bool
     */
    public function isValid(): bool
    {
        return 0 === count($this->messages);
    }

    /**
     * Return whether the condition has changed in comparison
     * to the previous state.
     *
     * This can also return true when there was previously no condition
     * or when the new condition is empty.
     *
     * @return bool
     */
    public function isChanged(): bool
    {
        return $this->changed;
    }
}
