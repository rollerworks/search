<?php

/*
 * This file is part of the RollerworksSearch package.
 *
 * (c) Sebastiaan Stok <s.stok@rollerscapes.net>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Rollerworks\Component\Search\Input\FilterQuery;

final class QueryException extends \Exception
{
    private $input;
    private $col;
    private $syntaxLine;
    private $expected;
    private $got;

    /**
     * @param string         $message
     * @param string         $input
     * @param int            $col
     * @param int            $line
     * @param array|string[] $expected
     * @param string         $got
     *
     * @return QueryException
     */
    public static function syntaxError($message, $input, $col, $line, $expected, $got)
    {
        $exp = new self('[Syntax Error] '.$message);
        $exp->input = $input;
        $exp->col = $col;
        $exp->syntaxLine = $line;
        $exp->expected = $expected;
        $exp->got = $got;

        return $exp;
    }

    /**
     * @return string
     */
    public function getInput()
    {
        return $this->input;
    }

    /**
     * @return int
     */
    public function getCol()
    {
        return $this->col;
    }

    /**
     * @return int
     */
    public function getSyntaxLine()
    {
        return $this->syntaxLine;
    }

    /**
     * @return string[]
     */
    public function getExpected()
    {
        return $this->expected;
    }

    /**
     * @return string
     */
    public function getInstead()
    {
        return $this->got;
    }
}
