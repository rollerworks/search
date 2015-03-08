<?php

/*
 * This file is part of the RollerworksSearch package.
 *
 * (c) Sebastiaan Stok <s.stok@rollerscapes.net>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Rollerworks\Component\Search\Doctrine\Orm\Functions;

use Doctrine\ORM\Query\AST\Functions\FunctionNode;
use Doctrine\ORM\Query\Lexer;
use Doctrine\ORM\Query\Parser;
use Doctrine\ORM\Query\SqlWalker;
use Rollerworks\Component\Search\Doctrine\Dbal\QueryPlatformInterface;

/**
 * "RW_SEARCH_MATCH(Column, Pattern, CaseInsensitive)".
 *
 * SearchValueMatch ::=
 *     "RW_SEARCH_MATCH" "(" StateFieldPathExpression "," StringPrimary "," Literal ")"
 *
 * @author Sebastiaan Stok <s.stok@rollerscapes.net>
 */
class ValueMatch extends FunctionNode
{
    /**
     * @var \Doctrine\ORM\Query\AST\PathExpression
     */
    private $column;

    /**
     * @var \Doctrine\ORM\Query\AST\Literal
     */
    private $pattern;

    /**
     * @var bool
     */
    private $caseInsensitive;

    /**
     * {@inheritdoc}
     */
    public function getSql(SqlWalker $sqlWalker)
    {
        /** @var \Closure $hintsValue */
        if (!$hintsValue = $sqlWalker->getQuery()->getHint('rw_where_builder')) {
            throw new \LogicException('Missing "rw_where_builder" hint for SearchValueMatch.');
        }

        /** @var QueryPlatformInterface $platform */
        list($platform, ) = $hintsValue();

        // Because Doctrine always requires an operator we use a sub-query with CASE
        $statement = $platform->getMatchSqlRegex(
            $sqlWalker->walkArithmeticPrimary($this->column),
            $sqlWalker->getQuery()->getEntityManager()->getConnection()->quote($this->pattern->value),
            $this->caseInsensitive,
            false
        );

        return "(CASE WHEN $statement THEN 1 ELSE 0 END)";
    }

    /**
     * {@inheritdoc}
     */
    public function parse(Parser $parser)
    {
        $parser->match(Lexer::T_IDENTIFIER);
        $parser->match(Lexer::T_OPEN_PARENTHESIS);

        $this->column = $parser->ArithmeticPrimary();
        $parser->match(Lexer::T_COMMA);
        $this->pattern = $parser->StringPrimary();
        $parser->match(Lexer::T_COMMA);
        $this->caseInsensitive = 'true' === strtolower($parser->Literal()->value);

        $parser->match(Lexer::T_CLOSE_PARENTHESIS);
    }
}
