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

use Rollerworks\Component\Search\ConditionErrorMessage;

/**
 * @author Sebastiaan Stok <s.stok@rollerscapes.net>
 */
final class InvalidSearchConditionException extends \InvalidArgumentException implements SearchException
{
    private $errors;

    public function __construct(array $errors)
    {
        parent::__construct('The search-condition contains one or more errors.');

        $this->errors = $errors;
    }

    /**
     * @return ConditionErrorMessage[]
     */
    public function getErrors(): array
    {
        return $this->errors;
    }
}
