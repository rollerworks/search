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

namespace Rollerworks\Component\Search;

use Rollerworks\Component\Search\Value\ValuesGroup;

/**
 * Works as a wrapper around the ValuesGroup, and ValuesBag transforming
 * input while ensuring restrictions are honored.
 *
 * @internal
 *
 * @author Sebastiaan Stok <s.stok@rollerscapes.net>
 */
interface StructureBuilder
{
    public function getErrors(): ErrorList;

    public function getCurrentPath();

    public function getRootGroup(): ValuesGroup;

    public function enterGroup(string $groupLocal = 'AND', string $path = '[%d]'): void;

    public function leaveGroup(): void;

    public function field(string $name, string $path): void;

    public function simpleValue($value, string $path): void;

    public function excludedSimpleValue($value, string $path): void;

    /**
     * @param array $path [path, lower-path-pattern, upper-path-pattern]
     */
    public function rangeValue($lower, $upper, bool $lowerInclusive, bool $upperInclusive, array $path): void;

    /**
     * @param array $path [path, lower-path-pattern, upper-path-pattern]
     */
    public function excludedRangeValue($lower, $upper, bool $lowerInclusive, bool $upperInclusive, array $path): void;

    /**
     * @param string $operator
     * @param array  $path     [base-path, operator-path, value-path]
     */
    public function comparisonValue($operator, $value, array $path): void;

    /**
     * @param string $type
     * @param string $value
     * @param array  $path  [base-path, value-path, type-path]
     */
    public function patterMatchValue($type, $value, bool $caseInsensitive, array $path): void;

    public function endValues();
}
