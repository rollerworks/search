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

namespace Rollerworks\Component\Search\Input;

use Rollerworks\Component\Search\ErrorList;
use Rollerworks\Component\Search\Field\FieldConfig;

/**
 * The Validator validates input values according to a set of
 * rules (constraints).
 *
 * @author Sebastiaan Stok <s.stok@rollerscapes.net>
 */
interface Validator
{
    /**
     * Initialize the validator context for the field.
     *
     * Whenever calling validate(), this context needs to be used.
     *
     * @param FieldConfig $field
     * @param ErrorList   $errorList
     */
    public function initializeContext(FieldConfig $field, ErrorList $errorList): void;

    /**
     * Validates and returns whether the value is valid.
     *
     * @param mixed $value
     * @param mixed $originalValue
     */
    public function validate($value, string $type, $originalValue, string $path): bool;
}
