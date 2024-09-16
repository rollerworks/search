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

namespace Rollerworks\Component\Search\Exception;

/**
 * UnsupportedValueTypeException.
 *
 * Throw this exception when the value-type is not supported for the field.
 */
final class UnsupportedValueTypeException extends InputProcessorException
{
    public function __construct(string $fieldName, string $valueType)
    {
        parent::__construct(
            '',
            'The field {{ field }} does not accept {{ type }} values.',
            [
                '{{ field }}' => $fieldName,
                '{{ type }}' => str_contains($valueType, '\\') ? mb_substr($valueType, mb_strrpos($valueType, '\\') + 1) : $valueType,
            ]
        );

        $this->setTranslatedParameters(['{{ type }}']);
    }
}
