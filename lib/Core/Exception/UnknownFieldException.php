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
 * @author Sebastiaan Stok <s.stok@rollerscapes.net>
 */
final class UnknownFieldException extends InputProcessorException
{
    public function __construct(string $fieldName)
    {
        parent::__construct(
            '',
            'Field {{ field }} is not registered in the FieldSet or available as alias.',
            ['{{ field }}' => $fieldName]
        );
    }
}
