<?php

/*
 * This file is part of the Rollerworks Search Component package.
 *
 * (c) Sebastiaan Stok <s.stok@rollerscapes.net>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Rollerworks\Component\Search\Exception;

/**
 * @author Sebastiaan Stok <s.stok@rollerscapes.net>
 */
class UnknownFieldException extends \InvalidArgumentException implements ExceptionInterface
{
    /**
     * @param string $fieldName
     */
    public function __construct($fieldName)
    {
        parent::__construct(sprintf('Field "%s" is not registered in the FieldSet or available as alias.', $fieldName));
    }
}
