<?php

/*
 * This file is part of the Rollerworks Search Component package.
 *
 * (c) Sebastiaan Stok <s.stok@rollerscapes.net>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Rollerworks\Component\Search;

/**
 * SearchCondition contains the searching conditions and FieldSet.
 */
interface SearchConditionInterface
{
    /**
     * @return FieldSet
     */
    public function getFieldSet();

    /**
     * @return ValuesGroup
     */
    public function getValuesGroup();
}
