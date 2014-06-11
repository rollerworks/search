<?php

/**
 * This file is part of the RollerworksSearch Component package.
 *
 * (c) 2012-2014 Sebastiaan Stok <s.stok@rollerscapes.net>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Rollerworks\Component\Search\Input\FilterQuery;

/**
 * QueryException.
 */
class QueryException extends \Exception
{
    /**
     * @param string          $message
     * @param \Exception|null $previous
     *
     * @return QueryException
     */
    public static function syntaxError($message, $previous = null)
    {
        return new self('[Syntax Error] ' . $message, 0, $previous);
    }

    /**
     * @param string          $message
     * @param \Exception|null $previous
     *
     * @return QueryException
     */
    public static function semanticError($message, $previous = null)
    {
        return new self('[Semantic Error] ' . $message, 0, $previous);
    }

    /**
     * @param string $literal
     *
     * @return QueryException
     */
    public static function invalidLiteral($literal)
    {
        return new self("Invalid literal '$literal'");
    }
}
