<?php

/*
 * This file is part of the Rollerworks Search Component package.
 *
 * (c) Sebastiaan Stok <s.stok@rollerscapes.net>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
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
